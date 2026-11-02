<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountOrderController;
use App\Http\Controllers\Admin\BlogCategoryController;
use App\Http\Controllers\Admin\BlogCommentController;
use App\Http\Controllers\Admin\BlogPostController;
use App\Http\Controllers\Admin\BlogTagController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Admin\FulfillmentController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\ProductCategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductTagController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\RedirectController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TestimonialController as AdminTestimonialController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutAccountController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CustomerAddressController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerEmailVerificationController;
use App\Http\Controllers\CustomerPasswordController;
use App\Http\Controllers\CustomerPaymentMethodController;
use App\Http\Controllers\CustomerProfileController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\PublicBlogController;
use App\Http\Controllers\PublicGalleryController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\PublicShopController;
use App\Http\Controllers\QuickOrderController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StripeWebhookController;
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

    // Products
    Route::get('/products', [ProductController::class, 'index'])->name('products.index')->middleware('can:products.view');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create')->middleware('can:products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store')->middleware('can:products.create');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit')->middleware('can:products.update');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update')->middleware('can:products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy')->middleware('can:products.delete');

    Route::get('/products/{product}/variants/create', [ProductVariantController::class, 'create'])->name('products.variants.create')->middleware('can:products.update');
    Route::post('/products/{product}/variants', [ProductVariantController::class, 'store'])->name('products.variants.store')->middleware('can:products.update');
    Route::get('/products/{product}/variants/{variant}/edit', [ProductVariantController::class, 'edit'])->name('products.variants.edit')->middleware('can:products.update');
    Route::put('/products/{product}/variants/{variant}', [ProductVariantController::class, 'update'])->name('products.variants.update')->middleware('can:products.update');
    Route::delete('/products/{product}/variants/{variant}', [ProductVariantController::class, 'destroy'])->name('products.variants.destroy')->middleware('can:products.update');

    Route::get('/product-categories', [ProductCategoryController::class, 'index'])->name('product-categories.index')->middleware('can:products.categories.manage');
    Route::get('/product-categories/create', [ProductCategoryController::class, 'create'])->name('product-categories.create')->middleware('can:products.categories.manage');
    Route::post('/product-categories', [ProductCategoryController::class, 'store'])->name('product-categories.store')->middleware('can:products.categories.manage');
    Route::get('/product-categories/{productCategory}/edit', [ProductCategoryController::class, 'edit'])->name('product-categories.edit')->middleware('can:products.categories.manage');
    Route::put('/product-categories/{productCategory}', [ProductCategoryController::class, 'update'])->name('product-categories.update')->middleware('can:products.categories.manage');
    Route::delete('/product-categories/{productCategory}', [ProductCategoryController::class, 'destroy'])->name('product-categories.destroy')->middleware('can:products.categories.manage');

    Route::get('/product-tags', [ProductTagController::class, 'index'])->name('product-tags.index')->middleware('can:products.tags.manage');
    Route::get('/product-tags/create', [ProductTagController::class, 'create'])->name('product-tags.create')->middleware('can:products.tags.manage');
    Route::post('/product-tags', [ProductTagController::class, 'store'])->name('product-tags.store')->middleware('can:products.tags.manage');
    Route::get('/product-tags/{productTag}/edit', [ProductTagController::class, 'edit'])->name('product-tags.edit')->middleware('can:products.tags.manage');
    Route::put('/product-tags/{productTag}', [ProductTagController::class, 'update'])->name('product-tags.update')->middleware('can:products.tags.manage');
    Route::delete('/product-tags/{productTag}', [ProductTagController::class, 'destroy'])->name('product-tags.destroy')->middleware('can:products.tags.manage');

    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index')->middleware('can:orders.view');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show')->middleware('can:orders.view');

    Route::post('/orders/{order}/ready-for-pickup', [OrderController::class, 'markReadyForPickup'])->name('orders.ready-for-pickup')->middleware('can:orders.fulfill');
    Route::post('/orders/{order}/picked-up', [OrderController::class, 'markPickedUp'])->name('orders.picked-up')->middleware('can:orders.fulfill');
    Route::post('/orders/{order}/out-for-delivery', [OrderController::class, 'markOutForDelivery'])->name('orders.out-for-delivery')->middleware('can:orders.fulfill');
    Route::post('/orders/{order}/delivered', [OrderController::class, 'markDelivered'])->name('orders.delivered')->middleware('can:orders.fulfill');
    Route::post('/orders/{order}/shipped', [OrderController::class, 'markShipped'])->name('orders.shipped')->middleware('can:orders.fulfill');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel')->middleware('can:orders.cancel');
    Route::post('/orders/{order}/refund', [OrderController::class, 'refund'])->name('orders.refund')->middleware('can:orders.refund');

    // Fulfillment
    Route::get('/fulfillment', [FulfillmentController::class, 'index'])->name('fulfillment.index')->middleware('can:fulfillment.view');
    Route::put('/fulfillment/settings', [FulfillmentController::class, 'updateSettings'])->name('fulfillment.settings.update')->middleware('can:fulfillment.manage');

    Route::post('/fulfillment/shipping-rules', [FulfillmentController::class, 'storeShippingRule'])->name('fulfillment.shipping-rules.store')->middleware('can:fulfillment.manage');
    Route::put('/fulfillment/shipping-rules/{shippingRate}', [FulfillmentController::class, 'updateShippingRule'])->name('fulfillment.shipping-rules.update')->middleware('can:fulfillment.manage');
    Route::delete('/fulfillment/shipping-rules/{shippingRate}', [FulfillmentController::class, 'destroyShippingRule'])->name('fulfillment.shipping-rules.destroy')->middleware('can:fulfillment.manage');

    Route::post('/fulfillment/delivery-zones', [FulfillmentController::class, 'storeDeliveryZone'])->name('fulfillment.delivery-zones.store')->middleware('can:fulfillment.manage');
    Route::put('/fulfillment/delivery-zones/{deliveryZone}', [FulfillmentController::class, 'updateDeliveryZone'])->name('fulfillment.delivery-zones.update')->middleware('can:fulfillment.manage');
    Route::delete('/fulfillment/delivery-zones/{deliveryZone}', [FulfillmentController::class, 'destroyDeliveryZone'])->name('fulfillment.delivery-zones.destroy')->middleware('can:fulfillment.manage');

    Route::post('/fulfillment/pickup-locations', [FulfillmentController::class, 'storePickupLocation'])->name('fulfillment.pickup-locations.store')->middleware('can:fulfillment.manage');
    Route::put('/fulfillment/pickup-locations/{pickupLocation}', [FulfillmentController::class, 'updatePickupLocation'])->name('fulfillment.pickup-locations.update')->middleware('can:fulfillment.manage');
    Route::delete('/fulfillment/pickup-locations/{pickupLocation}', [FulfillmentController::class, 'destroyPickupLocation'])->name('fulfillment.pickup-locations.destroy')->middleware('can:fulfillment.manage');
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

Route::get('/shop', [PublicShopController::class, 'index'])->name('shop.index');
Route::get('/shop/category/{productCategory:slug}', [PublicShopController::class, 'category'])->name('shop.category');
Route::get('/shop/tag/{productTag:slug}', [PublicShopController::class, 'tag'])->name('shop.tag');
Route::get('/shop/{product}', [PublicShopController::class, 'show'])->name('shop.show');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');
Route::patch('/cart/items/{cartItem}', [CartController::class, 'update'])->name('cart.items.update');
Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy'])->name('cart.items.destroy');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/return/{order}', [CheckoutController::class, 'paymentReturn'])
    ->name('checkout.return')
    ->middleware('signed');

Route::get('/checkout/confirmation/{order}', [CheckoutController::class, 'confirmation'])
    ->name('checkout.confirmation')
    ->middleware('signed');

Route::post('/checkout/confirmation/{order}/create-account', [CheckoutAccountController::class, 'store'])
    ->name('checkout.confirmation.account')
    ->middleware('signed');

Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

Route::get('/quick-order', [QuickOrderController::class, 'index'])->name('quick-order.index');
Route::post('/quick-order', [QuickOrderController::class, 'store'])->name('quick-order.store');

Route::get('/login', [CustomerAuthController::class, 'showLogin'])->name('customer.login');
Route::post('/login', [CustomerAuthController::class, 'login'])->name('customer.login.store')->middleware('throttle:login');
Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('customer.register');
Route::post('/register', [CustomerAuthController::class, 'register'])->name('customer.register.store')->middleware('throttle:register');
Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('customer.logout')->middleware('customer.auth');

Route::get('/forgot-password', [CustomerPasswordController::class, 'showForgot'])->name('customer.password.request');
Route::post('/forgot-password', [CustomerPasswordController::class, 'sendResetLink'])->name('customer.password.email')->middleware('throttle:6,1');
Route::get('/reset-password/{token}', [CustomerPasswordController::class, 'showReset'])->name('customer.password.reset');
Route::post('/reset-password', [CustomerPasswordController::class, 'reset'])->name('customer.password.store')->middleware('throttle:6,1');

Route::middleware('customer.auth')->group(function () {
    Route::get('/email/verify', [CustomerEmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [CustomerEmailVerificationController::class, 'verify'])->name('verification.verify')->middleware(['signed', 'throttle:6,1']);
    Route::post('/email/verification-notification', [CustomerEmailVerificationController::class, 'send'])->name('verification.send')->middleware('throttle:6,1');

    Route::prefix('account')->name('account.')->group(function () {
        Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
        Route::get('/profile', [CustomerProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [CustomerProfileController::class, 'update'])->name('profile.update');

        Route::get('/addresses', [CustomerAddressController::class, 'index'])->name('addresses.index');
        Route::get('/addresses/create', [CustomerAddressController::class, 'create'])->name('addresses.create');
        Route::post('/addresses', [CustomerAddressController::class, 'store'])->name('addresses.store');
        Route::get('/addresses/{address}/edit', [CustomerAddressController::class, 'edit'])->name('addresses.edit');
        Route::put('/addresses/{address}', [CustomerAddressController::class, 'update'])->name('addresses.update');
        Route::delete('/addresses/{address}', [CustomerAddressController::class, 'destroy'])->name('addresses.destroy');

        Route::get('/orders', [AccountOrderController::class, 'index'])->name('orders.index');
        Route::post('/orders/{order}/reorder', [AccountOrderController::class, 'reorder'])->name('orders.reorder');
        Route::get('/orders/{order}', [AccountOrderController::class, 'show'])->name('orders.show');

        Route::get('/payment-methods', [CustomerPaymentMethodController::class, 'index'])->name('payment-methods.index');
        Route::delete('/payment-methods/{paymentMethod}', [CustomerPaymentMethodController::class, 'destroy'])->name('payment-methods.destroy');
    });
});

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
    ->where('slug', '^(?!admin$|register$|login$|password$|logout$|reset-password$|_ignition$|sanctum$|telescope$|shop$|cart$|checkout$|quick-order$|blog$|register$|forgot-password$|account$|email$|faq$|contact$|gallery$|sitemap\.xml$|robots\.txt$)[a-z0-9]+(?:-[a-z0-9]+)*$');
