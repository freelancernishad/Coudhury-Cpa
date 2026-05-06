<?php

namespace App\Http\Controllers\Api\Admin\Blogs\Subscribers;

use App\Http\Controllers\Controller;
use App\Models\BlogSubscriber;
use Illuminate\Http\Request;

class BlogSubscriberController extends Controller
{
    /**
     * List all subscribers.
     */
    public function index()
    {
        $subscribers = BlogSubscriber::latest()->get();
        return response()->json($subscribers);
    }

    /**
     * Delete a subscriber.
     */
    public function destroy($id)
    {
        $subscriber = BlogSubscriber::find($id);

        if (!$subscriber) {
            return response()->json(['message' => 'Subscriber not found'], 404);
        }

        $subscriber->delete();

        return response()->json(['message' => 'Subscriber deleted successfully']);
    }

    /**
     * Toggle subscriber status.
     */
    public function toggleStatus($id)
    {
        $subscriber = BlogSubscriber::find($id);

        if (!$subscriber) {
            return response()->json(['message' => 'Subscriber not found'], 404);
        }

        $subscriber->is_active = !$subscriber->is_active;
        $subscriber->save();

        return response()->json([
            'message' => 'Status updated successfully',
            'is_active' => $subscriber->is_active
        ]);
    }
}
