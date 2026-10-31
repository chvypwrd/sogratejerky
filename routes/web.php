<?php

use App\Http\Controllers\Admin\BlogCategoryController;
use App\Http\Controllers\Admin\BlogCommentController;
use App\Http\Controllers\Admin\BlogPostController;
use App\Http\Controllers\Admin\BlogTagController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\RedirectController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TestimonialController as AdminTestimonialController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\PublicBlogController;
use App\Http\Controllers\PublicGalleryController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TestimonialController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'noindex.admin'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard')->middleware('can:dashboard.view');
    Route::get('/security', [SecurityController::class, 'index'])->name('security');

    // Menus
    Route::get('/menus', [MenuController::class, 'index'])->name('menus.index')->middleware('can:menus.view');
    Route::get('/menus/{menu}', [MenuController::class, 'edit'])->name('menus.edit')->middleware('can:menus.view');
    Route::put('/menus/{menu}', [MenuController::class, 'update'])->name('menus.update')->middleware('can:menus.manage');
    Route::post('/menus/{menu}/items', [MenuItemController::class, 'store'])->name('menus.items.store')->middleware('can:menus.manage');
    Route::get('/menus/{menu}/items/{menuItem}/edit', [MenuItemController::class, 'edit'])->name('menus.items.edit')->middleware('can:menus.manage');
    Route::put('/menus/{menu}/items/{menuItem}', [MenuItemController::class, 'update'])->name('menus.items.update')->middleware('can:menus.manage');
    Route::delete('/menus/{menu}/items/{menuItem}', [MenuItemController::class, 'destroy'])->name('menus.items.destroy')->middleware('can:menus.manage');
    Route::patch('/menus/{menu}/order', [MenuItemController::class, 'reorder'])->name('menus.order')->middleware('can:menus.manage');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index')->middleware('can:settings.view');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update')->middleware('can:settings.update');
    Route::post('/settings/populate', [SettingsController::class, 'populate'])->name('settings.populate')->middleware('can:settings.update');
    Route::post('/settings/reset', [SettingsController::class, 'reset'])->name('settings.reset')->middleware('can:settings.update');

    // Redirects
    Route::get('/redirects', [RedirectController::class, 'index'])->name('redirects.index')->middleware('can:redirects.view');
    Route::get('/redirects/create', [RedirectController::class, 'create'])->name('redirects.create')->middleware('can:redirects.manage');
    Route::post('/redirects', [RedirectController::class, 'store'])->name('redirects.store')->middleware('can:redirects.manage');
    Route::get('/redirects/{redirect}/edit', [RedirectController::class, 'edit'])->name('redirects.edit')->middleware('can:redirects.manage');
    Route::put('/redirects/{redirect}', [RedirectController::class, 'update'])->name('redirects.update')->middleware('can:redirects.manage');
    Route::delete('/redirects/{redirect}', [RedirectController::class, 'destroy'])->name('redirects.destroy')->middleware('can:redirects.manage');

    Route::get('/pages', [PageController::class, 'index'])->name('pages.index')->middleware('can:pages.view');
    Route::get('/pages/create', [PageController::class, 'create'])->name('pages.create')->middleware('can:pages.create');
    Route::post('/pages', [PageController::class, 'store'])->name('pages.store')->middleware('can:pages.create');
    Route::get('/pages/{page}/edit', [PageController::class, 'edit'])->name('pages.edit');
    Route::put('/pages/{page}', [PageController::class, 'update'])->name('pages.update');
    Route::delete('/pages/{page}', [PageController::class, 'destroy'])->name('pages.destroy');
    Route::post('/pages/{page}/restore', [PageController::class, 'restore'])->name('pages.restore');
    Route::delete('/pages/{page}/force-delete', [PageController::class, 'forceDelete'])->name('pages.force-delete');

    // Blog
    Route::get('/blog/posts', [BlogPostController::class, 'index'])->name('blog.posts.index')->middleware('can:blog.posts.view');
    Route::get('/blog/posts/create', [BlogPostController::class, 'create'])->name('blog.posts.create')->middleware('can:blog.posts.create');
    Route::post('/blog/posts', [BlogPostController::class, 'store'])->name('blog.posts.store')->middleware('can:blog.posts.create');
    Route::get('/blog/posts/{post}/edit', [BlogPostController::class, 'edit'])->name('blog.posts.edit')->middleware('can:blog.posts.update');
    Route::put('/blog/posts/{post}', [BlogPostController::class, 'update'])->name('blog.posts.update')->middleware('can:blog.posts.update');
    Route::delete('/blog/posts/{post}', [BlogPostController::class, 'destroy'])->name('blog.posts.destroy')->middleware('can:blog.posts.delete');
    Route::post('/blog/posts/{post}/publish', [BlogPostController::class, 'publish'])->name('blog.posts.publish')->middleware('can:blog.posts.update');
    Route::post('/blog/posts/{post}/unpublish', [BlogPostController::class, 'unpublish'])->name('blog.posts.unpublish')->middleware('can:blog.posts.update');

    Route::get('/blog/categories', [BlogCategoryController::class, 'index'])->name('blog.categories.index')->middleware('can:blog.categories.manage');
    Route::get('/blog/categories/create', [BlogCategoryController::class, 'create'])->name('blog.categories.create')->middleware('can:blog.categories.manage');
    Route::post('/blog/categories', [BlogCategoryController::class, 'store'])->name('blog.categories.store')->middleware('can:blog.categories.manage');
    Route::get('/blog/categories/{blogCategory}/edit', [BlogCategoryController::class, 'edit'])->name('blog.categories.edit')->middleware('can:blog.categories.manage');
    Route::put('/blog/categories/{blogCategory}', [BlogCategoryController::class, 'update'])->name('blog.categories.update')->middleware('can:blog.categories.manage');
    Route::delete('/blog/categories/{blogCategory}', [BlogCategoryController::class, 'destroy'])->name('blog.categories.destroy')->middleware('can:blog.categories.manage');

    Route::get('/blog/tags', [BlogTagController::class, 'index'])->name('blog.tags.index')->middleware('can:blog.tags.manage');
    Route::get('/blog/tags/create', [BlogTagController::class, 'create'])->name('blog.tags.create')->middleware('can:blog.tags.manage');
    Route::post('/blog/tags', [BlogTagController::class, 'store'])->name('blog.tags.store')->middleware('can:blog.tags.manage');
    Route::get('/blog/tags/{blogTag}/edit', [BlogTagController::class, 'edit'])->name('blog.tags.edit')->middleware('can:blog.tags.manage');
    Route::put('/blog/tags/{blogTag}', [BlogTagController::class, 'update'])->name('blog.tags.update')->middleware('can:blog.tags.manage');
    Route::delete('/blog/tags/{blogTag}', [BlogTagController::class, 'destroy'])->name('blog.tags.destroy')->middleware('can:blog.tags.manage');

    Route::get('/blog/comments', [BlogCommentController::class, 'index'])->name('blog.comments.index')->middleware('can:blog.comments.moderate');
    Route::post('/blog/comments/{comment}/approve', [BlogCommentController::class, 'approve'])->name('blog.comments.approve')->middleware('can:blog.comments.moderate');
    Route::post('/blog/comments/{comment}/reject', [BlogCommentController::class, 'reject'])->name('blog.comments.reject')->middleware('can:blog.comments.moderate');
    Route::delete('/blog/comments/{comment}', [BlogCommentController::class, 'destroy'])->name('blog.comments.destroy')->middleware('can:blog.comments.moderate');
    Route::post('/pages/{page}/publish', [PageController::class, 'publish'])->name('pages.publish');
    Route::post('/pages/{page}/unpublish', [PageController::class, 'unpublish'])->name('pages.unpublish');

    Route::get('/galleries', [GalleryController::class, 'index'])->name('galleries.index')->middleware('can:galleries.view');
    Route::get('/galleries/create', [GalleryController::class, 'create'])->name('galleries.create')->middleware('can:galleries.create');
    Route::post('/galleries', [GalleryController::class, 'store'])->name('galleries.store')->middleware('can:galleries.create');
    Route::get('/galleries/{gallery}/edit', [GalleryController::class, 'edit'])->name('galleries.edit');
    Route::put('/galleries/{gallery}', [GalleryController::class, 'update'])->name('galleries.update');
    Route::delete('/galleries/{gallery}', [GalleryController::class, 'destroy'])->name('galleries.destroy');
    Route::post('/galleries/{gallery}/restore', [GalleryController::class, 'restore'])->name('galleries.restore')->withTrashed();
    Route::delete('/galleries/{gallery}/force-delete', [GalleryController::class, 'forceDelete'])->name('galleries.force-delete')->withTrashed();
    Route::patch('/galleries/{gallery}/reorder', [GalleryController::class, 'reorder'])->name('galleries.reorder');

    Route::get('/testimonials', [AdminTestimonialController::class, 'index'])->name('testimonials.index')->middleware('can:testimonials.view');
    Route::get('/testimonials/create', [AdminTestimonialController::class, 'create'])->name('testimonials.create')->middleware('can:testimonials.create');
    Route::post('/testimonials', [AdminTestimonialController::class, 'store'])->name('testimonials.store')->middleware('can:testimonials.create');
    Route::get('/testimonials/{testimonial}/edit', [AdminTestimonialController::class, 'edit'])->name('testimonials.edit');
    Route::put('/testimonials/{testimonial}', [AdminTestimonialController::class, 'update'])->name('testimonials.update');
    Route::delete('/testimonials/{testimonial}', [AdminTestimonialController::class, 'destroy'])->name('testimonials.destroy');
    Route::post('/testimonials/{testimonial}/restore', [AdminTestimonialController::class, 'restore'])->name('testimonials.restore')->withTrashed();
    Route::delete('/testimonials/{testimonial}/force-delete', [AdminTestimonialController::class, 'forceDelete'])->name('testimonials.force-delete')->withTrashed();

    Route::get('/faqs', [AdminFaqController::class, 'index'])->name('faqs.index')->middleware('can:faqs.view');
    Route::get('/faqs/create', [AdminFaqController::class, 'create'])->name('faqs.create')->middleware('can:faqs.create');
    Route::post('/faqs', [AdminFaqController::class, 'store'])->name('faqs.store')->middleware('can:faqs.create');
    Route::get('/faqs/{faq}/edit', [AdminFaqController::class, 'edit'])->name('faqs.edit');
    Route::put('/faqs/{faq}', [AdminFaqController::class, 'update'])->name('faqs.update');
    Route::delete('/faqs/{faq}', [AdminFaqController::class, 'destroy'])->name('faqs.destroy');
    Route::post('/faqs/{faq}/restore', [AdminFaqController::class, 'restore'])->name('faqs.restore')->withTrashed();
    Route::delete('/faqs/{faq}/force-delete', [AdminFaqController::class, 'forceDelete'])->name('faqs.force-delete')->withTrashed();

    // Contact Messages
    Route::get('/contact-messages', [ContactMessageController::class, 'index'])->name('contact-messages.index');
    Route::get('/contact-messages/{contactMessage}', [ContactMessageController::class, 'show'])->name('contact-messages.show');
    Route::post('/contact-messages/{contactMessage}/mark-read', [ContactMessageController::class, 'markRead'])->name('contact-messages.mark-read');
    Route::post('/contact-messages/{contactMessage}/mark-unread', [ContactMessageController::class, 'markUnread'])->name('contact-messages.mark-unread');
    Route::post('/contact-messages/{contactMessage}/mark-replied', [ContactMessageController::class, 'markReplied'])->name('contact-messages.mark-replied');
    Route::post('/contact-messages/{contactMessage}/archive', [ContactMessageController::class, 'archive'])->name('contact-messages.archive');
    Route::post('/contact-messages/{contactMessage}/spam', [ContactMessageController::class, 'spam'])->name('contact-messages.spam');
    Route::post('/contact-messages/{contactMessage}/restore', [ContactMessageController::class, 'restore'])->name('contact-messages.restore');
    Route::delete('/contact-messages/{contactMessage}', [ContactMessageController::class, 'destroy'])->name('contact-messages.destroy');
});

Route::get('/admin/preview/{page}', [PreviewController::class, 'show'])
    ->name('pages.preview')
    ->middleware(['signed', 'auth']);

Route::get('/blog', [PublicBlogController::class, 'index'])->name('blog.index');
Route::get('/blog/search', [PublicBlogController::class, 'search'])->name('blog.search');
Route::get('/blog/feed', [PublicBlogController::class, 'feed'])->name('blog.feed');
Route::get('/blog/author/{author}', [PublicBlogController::class, 'author'])->name('blog.author');
Route::get('/blog/category/{blogCategory:slug}', [PublicBlogController::class, 'category'])->name('blog.category');
Route::get('/blog/tag/{blogTag:slug}', [PublicBlogController::class, 'tag'])->name('blog.tag');
Route::get('/blog/{slug}', [PublicBlogController::class, 'show'])->name('blog.show');
Route::post('/blog/{slug}/comments', [PublicBlogController::class, 'storeComment'])->name('blog.comments.store')->middleware('throttle:blog-comments');

Route::get('/faq', [FaqController::class, 'index'])->name('faq.index');
Route::get('/faqs', [FaqController::class, 'index'])->name('faqs.index');
Route::get('/testimonials', [TestimonialController::class, 'index'])->name('testimonials.index');
Route::get('/galleries', [PublicGalleryController::class, 'index'])->name('galleries.index');

Route::get('/contact', [ContactController::class, 'show'])->name('contact.show');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store')->middleware('throttle:contact');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots');

Route::get('/gallery', [PublicGalleryController::class, 'index'])->name('gallery.index');
Route::get('/gallery/{gallery:slug}', [PublicGalleryController::class, 'show'])->name('gallery.show');

Route::get('/{slug}', PublicPageController::class)->name('pages.show')
    ->where('slug', '^(?!admin$|register$|login$|password$|logout$|reset-password$|_ignition$|sanctum$|telescope$|blog$|faq$|contact$|gallery$|sitemap\.xml$|robots\.txt$)[a-z0-9]+(?:-[a-z0-9]+)*$');
