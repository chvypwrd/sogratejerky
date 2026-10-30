<?php

namespace Tests\Feature\Seo;

use App\Models\ClassOffering;
use App\Models\Instructor;
use App\Models\Setting;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class StructuredDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    protected function getJsonLdFromResponse($response): array
    {
        $content = $response->getContent();

        preg_match('/<script type="application\/ld\+json">(.+?)<\/script>/s', $content, $matches);

        $this->assertNotEmpty($matches, 'No JSON-LD script tag found in response');

        $data = json_decode($matches[1], true);

        $this->assertIsArray($data, 'JSON-LD content is not valid JSON');

        return $data;
    }

    public function test_homepage_contains_valid_json_ld(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('application/ld+json', false);
    }

    public function test_json_ld_decodes_successfully(): void
    {
        $response = $this->get(route('home'));

        $data = $this->getJsonLdFromResponse($response);

        $this->assertArrayHasKey('@context', $data);
        $this->assertEquals('https://schema.org', $data['@context']);
    }

    public function test_organization_type_present(): void
    {
        $response = $this->get(route('home'));

        $data = $this->getJsonLdFromResponse($response);

        $this->assertArrayHasKey('@graph', $data);
        $graph = $data['@graph'];

        $organization = collect($graph)->first(fn ($item) => $item['@type'] === 'Organization');
        $this->assertNotNull($organization, 'Organization type not found in @graph');
    }

    public function test_website_type_present(): void
    {
        $response = $this->get(route('home'));

        $data = $this->getJsonLdFromResponse($response);

        $graph = $data['@graph'];
        $website = collect($graph)->first(fn ($item) => $item['@type'] === 'WebSite');
        $this->assertNotNull($website, 'WebSite type not found in @graph');
    }

    public function test_organization_name_from_settings(): void
    {
        Setting::setValue('general', 'site_name', 'Test Org Name');

        $response = $this->get(route('home'));

        $data = $this->getJsonLdFromResponse($response);
        $graph = $data['@graph'];

        $organization = collect($graph)->first(fn ($item) => $item['@type'] === 'Organization');
        $this->assertEquals('Test Org Name', $organization['name']);
    }

    public function test_website_url_correct(): void
    {
        $response = $this->get(route('home'));

        $data = $this->getJsonLdFromResponse($response);
        $graph = $data['@graph'];

        $website = collect($graph)->first(fn ($item) => $item['@type'] === 'WebSite');
        $this->assertEquals(url('/'), $website['url']);
    }

    public function test_ids_stable(): void
    {
        $response = $this->get(route('home'));

        $data = $this->getJsonLdFromResponse($response);
        $graph = $data['@graph'];

        $organization = collect($graph)->first(fn ($item) => $item['@type'] === 'Organization');
        $website = collect($graph)->first(fn ($item) => $item['@type'] === 'WebSite');

        $this->assertStringContainsString('#organization', $organization['@id'] ?? '');
        $this->assertStringContainsString('#website', $website['@id'] ?? '');
    }

    public function test_sameas_only_when_social_profiles_configured(): void
    {
        $response = $this->get(route('home'));

        $data = $this->getJsonLdFromResponse($response);
        $graph = $data['@graph'];

        $organization = collect($graph)->first(fn ($item) => $item['@type'] === 'Organization');

        $this->assertArrayNotHasKey('sameAs', $organization, 'sameAs should not be present when no social profiles configured');

        Setting::setValue('social', 'facebook', 'https://facebook.com/test');
        Cache::flush();

        $response = $this->get(route('home'));

        $data = $this->getJsonLdFromResponse($response);
        $graph = $data['@graph'];

        $organization = collect($graph)->first(fn ($item) => $item['@type'] === 'Organization');

        $this->assertArrayHasKey('sameAs', $organization);
        $this->assertContains('https://facebook.com/test', $organization['sameAs']);
    }

    public function test_no_blank_null_required_properties(): void
    {
        $response = $this->get(route('home'));

        $data = $this->getJsonLdFromResponse($response);
        $graph = $data['@graph'];

        foreach ($graph as $item) {
            $this->assertNotEmpty($item['@type'], 'Item in @graph has empty @type');
            $this->assertNotEmpty($item['name'], "Item {$item['@type']} has empty name");
        }
    }

    public function test_malicious_script_tag_in_settings_does_not_break_json_ld(): void
    {
        Setting::setValue('general', 'site_name', '</script><script>alert("xss")</script>');
        Cache::flush();

        $response = $this->get(route('home'));

        $data = $this->getJsonLdFromResponse($response);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('@graph', $data);

        $response->assertDontSee('</script><script>alert("xss")</script>', false);
    }

    public function test_contact_page_has_no_structured_data(): void
    {
        $response = $this->get(route('contact.show'));

        $response->assertOk();
        $response->assertDontSee('application/ld+json', false);
    }

    public function test_class_detail_page_has_breadcrumb_list(): void
    {
        $class = ClassOffering::factory()->active()->create([
            'slug' => 'test-breadcrumb-class',
        ]);

        $response = $this->get(route('classes.show', 'test-breadcrumb-class'));

        $response->assertOk();

        $content = $response->getContent();

        preg_match_all('/<script type="application\/ld\+json">(.+?)<\/script>/s', $content, $matches);

        $foundBreadcrumb = false;

        foreach ($matches[1] as $jsonString) {
            $data = json_decode($jsonString, true);
            if ($data && isset($data['@type']) && $data['@type'] === 'BreadcrumbList') {
                $foundBreadcrumb = true;
                break;
            }
            if ($data && isset($data['@graph'])) {
                foreach ($data['@graph'] as $item) {
                    if (isset($item['@type']) && $item['@type'] === 'BreadcrumbList') {
                        $foundBreadcrumb = true;
                        break 2;
                    }
                }
            }
        }

        $this->assertTrue($foundBreadcrumb, 'Class detail page should have BreadcrumbList structured data');
    }

    public function test_instructor_detail_page_has_breadcrumb_list(): void
    {
        $instructor = Instructor::factory()->active()->create([
            'slug' => 'test-breadcrumb-instructor',
        ]);

        $response = $this->get(route('instructors.show', 'test-breadcrumb-instructor'));

        $response->assertOk();

        $content = $response->getContent();

        preg_match_all('/<script type="application\/ld\+json">(.+?)<\/script>/s', $content, $matches);

        $foundBreadcrumb = false;

        foreach ($matches[1] as $jsonString) {
            $data = json_decode($jsonString, true);
            if ($data && isset($data['@type']) && $data['@type'] === 'BreadcrumbList') {
                $foundBreadcrumb = true;
                break;
            }
            if ($data && isset($data['@graph'])) {
                foreach ($data['@graph'] as $item) {
                    if (isset($item['@type']) && $item['@type'] === 'BreadcrumbList') {
                        $foundBreadcrumb = true;
                        break 2;
                    }
                }
            }
        }

        $this->assertTrue($foundBreadcrumb, 'Instructor detail page should have BreadcrumbList structured data');
    }
}
