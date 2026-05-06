<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\BlogSubscriber;
use App\Mail\SubscriptionWelcome;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

class PublicBlogController extends Controller
{
    /**
     * Get all blog categories.
     */
    public function categories()
    {
        $categories = Category::whereNull('parent_id')
            ->with('children')
            ->get();
        
        return response()->json($categories);
    }

    /**
     * Get all blog articles.
     */
    public function articles(Request $request)
    {
        $query = Article::with('categories')->latest();

        if ($request->has('category_id')) {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('categories.id', $request->category_id);
            });
        }

        $articles = $query->get();

        return response()->json($articles);
    }

    /**
     * Get article by ID or Slug.
     */
    public function show($idOrSlug)
    {
        $article = Article::with('categories')
            ->where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->first();

        if (!$article) {
            return response()->json(['error' => 'Article not found'], 404);
        }

        return response()->json($article);
    }

    /**
     * Get articles by category with children.
     */
    public function articlesByCategory(Request $request)
    {
        $categoryId = $request->category_id;
        
        if (!$categoryId) {
            return response()->json(['error' => 'Category ID is required'], 400);
        }

        $articles = Article::whereHas('categories', function($q) use ($categoryId) {
            $q->where('categories.id', $categoryId);
        })->with('categories')->latest()->get();

        return response()->json($articles);
    }

    /**
     * Subscribe to newsletter.
     */
    public function subscribe(Request $request)
    {
        $email = $request->email;

        // Check if subscriber already exists
        $subscriber = BlogSubscriber::where('email', $email)->first();

        if ($subscriber) {
            if ($subscriber->is_active) {
                return response()->json([
                    'errors' => ['email' => ['You are already subscribed to our newsletter.']]
                ], 400);
            }

            // If inactive, re-activate
            $subscriber->is_active = true;
            $subscriber->save();

            // Send Welcome Email
            Mail::to($subscriber->email)->send(new SubscriptionWelcome($subscriber));

            return response()->json(['message' => 'Welcome back! You have successfully re-subscribed.']);
        }

        // If new email, validate and create
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:blog_subscribers,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $subscriber = BlogSubscriber::create([
            'email' => $request->email,
            'is_active' => true,
        ]);

        // Send Welcome Email
        Mail::to($subscriber->email)->send(new SubscriptionWelcome($subscriber));

        return response()->json(['message' => 'Subscribed successfully!']);
    }

    /**
     * Unsubscribe from newsletter.
     */
    public function unsubscribe(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $subscriber = BlogSubscriber::where('unsubscribe_token', $request->token)->first();

        if (!$subscriber) {
            return response()->json(['message' => 'Invalid or expired unsubscribe token'], 404);
        }

        $subscriber->is_active = false;
        $subscriber->save();

        return response()->json(['message' => 'Unsubscribed successfully.']);
    }
}
