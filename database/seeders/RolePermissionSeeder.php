<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'pages.view', 'pages.create', 'pages.update', 'pages.delete', 'pages.publish',
            'blog.posts.view', 'blog.posts.create', 'blog.posts.update', 'blog.posts.delete',
            'blog.categories.manage', 'blog.tags.manage', 'blog.comments.moderate',
            'products.view', 'products.create', 'products.update', 'products.delete',
            'products.categories.manage', 'products.tags.manage',
            'fulfillment.view', 'fulfillment.manage',
            'testimonials.view', 'testimonials.create', 'testimonials.update', 'testimonials.delete',
            'faqs.view', 'faqs.create', 'faqs.update', 'faqs.delete',
            'menus.view', 'menus.manage',
            'media.view', 'media.upload', 'media.delete',
            'contacts.view', 'contacts.update', 'contacts.delete',
            'settings.view', 'settings.update',
            'redirects.view', 'redirects.manage',
            'galleries.view', 'galleries.create', 'galleries.update', 'galleries.delete',
            'users.view', 'users.create', 'users.update', 'users.delete',
            'roles.view', 'roles.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions($permissions);

        $administrator = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        $administrator->syncPermissions([
            'dashboard.view',
            'pages.view', 'pages.create', 'pages.update', 'pages.delete', 'pages.publish',
            'blog.posts.view', 'blog.posts.create', 'blog.posts.update', 'blog.posts.delete',
            'blog.categories.manage', 'blog.tags.manage', 'blog.comments.moderate',
            'products.view', 'products.create', 'products.update', 'products.delete',
            'products.categories.manage', 'products.tags.manage',
            'fulfillment.view', 'fulfillment.manage',
            'testimonials.view', 'testimonials.create', 'testimonials.update', 'testimonials.delete',
            'faqs.view', 'faqs.create', 'faqs.update', 'faqs.delete',
            'menus.view', 'menus.manage',
            'media.view', 'media.upload', 'media.delete',
            'contacts.view', 'contacts.update', 'contacts.delete',
            'settings.view', 'settings.update',
            'redirects.view', 'redirects.manage',
            'galleries.view', 'galleries.create', 'galleries.update', 'galleries.delete',
            'users.view',
        ]);

        $editor = Role::firstOrCreate(['name' => 'Editor', 'guard_name' => 'web']);
        $editor->syncPermissions([
            'dashboard.view',
            'pages.view', 'pages.create', 'pages.update', 'pages.publish',
            'blog.posts.view', 'blog.posts.create', 'blog.posts.update',
            'blog.categories.manage', 'blog.tags.manage', 'blog.comments.moderate',
            'products.view', 'products.create', 'products.update',
            'products.categories.manage', 'products.tags.manage',
            'testimonials.view', 'testimonials.create', 'testimonials.update',
            'faqs.view', 'faqs.create', 'faqs.update',
            'media.view', 'media.upload',
            'galleries.view', 'galleries.create', 'galleries.update',
        ]);
    }
}
