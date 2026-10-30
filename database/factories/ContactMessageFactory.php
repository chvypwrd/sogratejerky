<?php

namespace Database\Factories;

use App\Enums\ContactMessageStatus;
use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    protected $model = ContactMessage::class;

    public function definition(): array
    {
        return [
            'name' => \Faker\Factory::create()->name(),
            'email' => \Faker\Factory::create()->safeEmail(),
            'phone' => \Faker\Factory::create()->phoneNumber(),
            'subject' => \Faker\Factory::create()->sentence(4),
            'message' => \Faker\Factory::create()->paragraph(3),
            'status' => ContactMessageStatus::New,
            'ip_address' => \Faker\Factory::create()->ipv4(),
            'user_agent' => \Faker\Factory::create()->userAgent(),
        ];
    }

    public function newMessage(): static
    {
        return $this->state(fn () => [
            'status' => ContactMessageStatus::New,
            'read_at' => null,
            'replied_at' => null,
        ]);
    }

    public function read(): static
    {
        return $this->state(fn () => [
            'status' => ContactMessageStatus::Read,
            'read_at' => \Faker\Factory::create()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    public function replied(): static
    {
        return $this->state(fn () => [
            'status' => ContactMessageStatus::Replied,
            'read_at' => \Faker\Factory::create()->dateTimeBetween('-2 weeks', '-1 week'),
            'replied_at' => \Faker\Factory::create()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn () => [
            'status' => ContactMessageStatus::Archived,
        ]);
    }

    public function spam(): static
    {
        return $this->state(fn () => [
            'status' => ContactMessageStatus::Spam,
        ]);
    }
}
