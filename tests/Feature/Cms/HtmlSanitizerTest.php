<?php

namespace Tests\Feature\Cms;

use App\Services\HtmlSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HtmlSanitizerTest extends TestCase
{
    use RefreshDatabase;

    private HtmlSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = app(HtmlSanitizer::class);
    }

    public function test_safe_html_passes_through(): void
    {
        $input = '<p>Hello <strong>world</strong></p>';
        $this->assertSame($input, $this->sanitizer->clean($input));
    }

    public function test_script_tags_removed(): void
    {
        $input = '<p>Hello</p><script>alert("xss")</script>';
        $result = $this->sanitizer->clean($input);
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('alert', $result);
    }

    public function test_event_handlers_removed(): void
    {
        $input = '<p onclick="alert(1)">Click me</p>';
        $result = $this->sanitizer->clean($input);
        $this->assertStringNotContainsString('onclick', $result);
    }

    public function test_onerror_handler_removed(): void
    {
        $input = '<p onerror="alert(1)">Content</p>';
        $result = $this->sanitizer->clean($input);
        $this->assertStringNotContainsString('onerror', $result);
    }

    public function test_empty_string_returns_empty(): void
    {
        $this->assertSame('', $this->sanitizer->clean(''));
    }

    public function test_null_returns_empty(): void
    {
        $this->assertSame('', $this->sanitizer->clean(null));
    }

    public function test_allowed_elements_preserved(): void
    {
        $elements = [
            'p', 'strong', 'em', 'b', 'i', 'h2', 'h3', 'h4', 'blockquote', 'a', 'hr',
        ];

        foreach ($elements as $tag) {
            $input = "<{$tag}>Content</{$tag}>";
            $result = $this->sanitizer->clean($input);
            $this->assertStringContainsString("<{$tag}", $result, "Element <{$tag}> should be preserved");
        }

        // <br> is self-closing
        $this->assertStringContainsString('<br', $this->sanitizer->clean('<br>'));

        // <li> needs a parent list
        $this->assertStringContainsString('<li>', $this->sanitizer->clean('<ul><li>Item</li></ul>'));
        $this->assertStringContainsString('<li>', $this->sanitizer->clean('<ol><li>Item</li></ol>'));
        $this->assertStringContainsString('<ul>', $this->sanitizer->clean('<ul><li>Item</li></ul>'));
        $this->assertStringContainsString('<ol>', $this->sanitizer->clean('<ol><li>Item</li></ol>'));
    }

    public function test_disallowed_elements_removed(): void
    {
        $disallowed = ['img', 'div', 'span', 'table', 'h1', 'h5', 'h6', 'iframe', 'script', 'style', 'form', 'input'];

        foreach ($disallowed as $tag) {
            $input = "<{$tag}>Content</{$tag}>";
            $result = $this->sanitizer->clean($input);
            $this->assertStringNotContainsString("<{$tag}", $result, "Element <{$tag}> should be removed");
        }
    }

    public function test_disallowed_attributes_removed(): void
    {
        $input = '<p onclick="evil()" style="color:red">Text</p>';
        $result = $this->sanitizer->clean($input);
        $this->assertStringNotContainsString('onclick', $result);
        $this->assertStringNotContainsString('style', $result);
    }

    public function test_javascript_url_removed(): void
    {
        $input = '<a href="javascript:alert(1)">Click</a>';
        $result = $this->sanitizer->clean($input);
        $this->assertStringNotContainsString('javascript:', $result);
    }

    public function test_data_url_removed(): void
    {
        $input = '<a href="data:text/html,<script>alert(1)</script>">Click</a>';
        $result = $this->sanitizer->clean($input);
        $this->assertStringNotContainsString('data:', $result);
    }

    public function test_http_url_retained(): void
    {
        $input = '<a href="http://example.com">Link</a>';
        $result = $this->sanitizer->clean($input);
        $this->assertStringContainsString('http://example.com', $result);
    }

    public function test_https_url_retained(): void
    {
        $input = '<a href="https://example.com">Link</a>';
        $result = $this->sanitizer->clean($input);
        $this->assertStringContainsString('https://example.com', $result);
    }

    public function test_mailto_url_retained(): void
    {
        $input = '<a href="mailto:test@example.com">Email</a>';
        $result = $this->sanitizer->clean($input);
        $this->assertStringContainsString('mailto:test@example.com', $result);
    }

    public function test_tel_url_retained(): void
    {
        $input = '<a href="tel:+1234567890">Phone</a>';
        $result = $this->sanitizer->clean($input);
        $this->assertStringContainsString('tel:+1234567890', $result);
    }

    public function test_link_href_preserved(): void
    {
        $input = '<a href="https://example.com">Link</a>';
        $result = $this->sanitizer->clean($input);
        $this->assertStringContainsString('href="https://example.com"', $result);
    }

    public function test_is_allowed_returns_true_for_allowed_elements(): void
    {
        $this->assertTrue($this->sanitizer->isAllowed('p'));
        $this->assertTrue($this->sanitizer->isAllowed('strong'));
        $this->assertTrue($this->sanitizer->isAllowed('a'));
        $this->assertTrue($this->sanitizer->isAllowed('h2'));
    }

    public function test_is_allowed_returns_false_for_disallowed_elements(): void
    {
        $this->assertFalse($this->sanitizer->isAllowed('script'));
        $this->assertFalse($this->sanitizer->isAllowed('iframe'));
        $this->assertFalse($this->sanitizer->isAllowed('img'));
        $this->assertFalse($this->sanitizer->isAllowed('div'));
    }
}
