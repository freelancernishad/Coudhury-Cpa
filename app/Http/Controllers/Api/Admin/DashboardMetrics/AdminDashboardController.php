<?php

namespace App\Http\Controllers\Api\Admin\DashboardMetrics;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Package;
use App\Models\Payment;
use App\Models\UserPackage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class AdminDashboardController extends Controller
{
    /**
     * Display the dashboard metrics.
     *
     * @return JsonResponse
     */


     public function index(Request $request)
     {
         $year = $request->year ?? now()->year;
         $week = $request->week ?? 'current';

         $fromDate = $request->from_date;


         $toDate = isset($request->to_date) ? $request->to_date : $fromDate;





         // Total users
         $totalUsers = User::count();

         // New registrations in the last 7 days
         $newRegistrations = User::where('created_at', '>=', now()->subDays(7))->count();

         // Subscribed users
         $subscribedUsers = UserPackage::where('started_at', '<=', Carbon::now())
         ->where('ends_at', '>=', Carbon::now())
         ->with('user')  // Eager load the related user
         ->get()
         ->pluck('user')
         ->unique('id'); // Ensure unique users in case of multiple packages

        // To get the count of subscribed users
        $subscribedUserCount = $subscribedUsers->count();

         // Pending verifications
         $pendingVerifications = User::whereNull('email_verified_at')->count();

         // Package revenue data (monthly, yearly, weekly)
         $packageRevenueData = getPackageRevenueData($year, $week);

         // Total revenue by package
         $totalRevenueByPackage = $packageRevenueData['total_revenue_per_package'];

         // Weekly package revenue max value
         $weeklyPackageRevenueMax = $packageRevenueData['weekly_package_revenue_max'];

         // Calculate revenue by package within a date range if provided
         $revenueByDate = [];
         if ($fromDate) {
             $revenueByDate = Package::all()->map(function ($package) use ($fromDate, $toDate) {
                 // Query to get total revenue for the package within the specified date range or day
                 $totalAmountQuery = Payment::where('payable_type', 'Package')
                     ->where('payable_id', $package->id)
                     ->completed(); // Use the 'completed' scope to filter by completed payments

                 // Check if 'toDate' is undefined and apply appropriate date filter
                 if ($toDate === 'undefined') {
                     $fromDate = date("Y-m-d", strtotime($fromDate));
                     $totalAmountQuery->whereDate('paid_at', $fromDate);
                 } else {
                     $totalAmountQuery->whereBetween('paid_at', [$fromDate, $toDate]);
                 }

                 // Sum the total amount
                 $totalAmount = $totalAmountQuery->sum('amount');

                 return [
                     'name' => $package->package_name,
                     'total_amount' => (int) $totalAmount, // Cast to integer
                 ];
             })->toArray();
         }


        // Calculate total revenue across all packages
        $totalRevenue = Payment::where('payable_type', 'Package')
            ->completed()  // Use the 'completed' scope for completed payments
            ->sum('amount');


         return response()->json([
             'total_users' => $totalUsers,
             'new_registrations' => $newRegistrations,
             'subscribed_users' => $subscribedUserCount,
             'pending_verifications' => $pendingVerifications,
             'package_revenue' => $packageRevenueData['monthly_package_revenue'],
             'package_revenue_max' => $packageRevenueData['monthly_package_revenue_max'],
             'total_revenue_per_package' => $totalRevenueByPackage,
             'yearly_package_revenue' => $packageRevenueData['yearly_package_revenue'],
             'weekly_package_revenue' => $packageRevenueData['weekly_package_revenue'],
             'weekly_package_revenue_max' => $weeklyPackageRevenueMax,
             'revenue_by_date' => $revenueByDate, // Revenue by package within date range
             'total_revenue' => (int) $totalRevenue, // Total revenue across all packages
         ]);
     }

     public function getAdminMatrix(Request $request)
     {
         // Get the year from the request, or default to the current year
         $year = $request->input('year', now()->year);

         // Get total number of users (clients)
         $totalClients = User::count();

         // Get new clients who registered in the last 7 days
         $newClients = User::where('created_at', '>=', now()->subDays(7))->count();

         // Get active clients (users who have services with status "In Review")
         $activeClients = User::whereHas('servicePurchased', function ($query) {
             $query->where('status', 'In Review');
         })->count();

         // Get total payment amount for completed payments
         $totalPaymentsAmount = (int) Payment::where('status', 'completed')->sum('amount'); // Only completed payments and cast to int

         // Prepare months for the selected year (January to December)
         $months = collect(range(1, 12))->map(function ($month) use ($year) {
             return now()->setYear($year)->month($month)->format('F Y');
         });

         // Initialize arrays to store the monthly data for each series
         $dueAmountData = [];
         $servicePurchaseData = [];
         $packagePurchaseData = [];

         // Fetch the data for each event type (Due Amount, Service Purchase, Package Purchase) for each month
         foreach ($months as $month) {
             // Due Amount for the selected year
             $dueAmountData[] = (int) Payment::where('status', 'completed')
                 ->where('event', 'Due Amount')  // Using "event" field for the type of event
                 ->whereBetween('paid_at', [
                     now()->setYear($year)->month($months->search($month) + 1)->startOfMonth(),
                     now()->setYear($year)->month($months->search($month) + 1)->endOfMonth()
                 ])
                 ->sum('amount'); // Cast to int

             // Service Purchase for the selected year
             $servicePurchaseData[] = (int) Payment::where('status', 'completed')
                 ->where('event', 'Service Purchase')  // Using "event" field for the type of event
                 ->whereBetween('paid_at', [
                     now()->setYear($year)->month($months->search($month) + 1)->startOfMonth(),
                     now()->setYear($year)->month($months->search($month) + 1)->endOfMonth()
                 ])
                 ->sum('amount'); // Cast to int

             // Package Purchase for the selected year
             $packagePurchaseData[] = (int) Payment::where('status', 'completed')
                 ->where('event', 'Package Purchase')  // Using "event" field for the type of event
                 ->whereBetween('paid_at', [
                     now()->setYear($year)->month($months->search($month) + 1)->startOfMonth(),
                     now()->setYear($year)->month($months->search($month) + 1)->endOfMonth()
                 ])
                 ->sum('amount'); // Cast to int
         }


             // Get the latest 10 registered users with selected fields
        $latestUsers = User::select('name', 'profile_picture', 'client_id', 'status')
        ->latest()
        ->take(10)
        ->get();


         // Prepare the final matrix with series data for chart
         $adminMatrix = [
             'new_clients' => $newClients,
             'total_clients' => $totalClients,
             'active_clients' => $activeClients,
             'total_payments_amount' => $totalPaymentsAmount,
             'year' => $year,
             'latest_users' => $latestUsers,
             'series' => [
                 [
                     'name' => 'Due Amount',
                     'data' => $dueAmountData
                 ],
                 [
                     'name' => 'Service Purchase',
                     'data' => $servicePurchaseData
                 ],
                 [
                     'name' => 'Package Purchase',
                     'data' => $packagePurchaseData
                 ]
             ],
             'categories' => $months, // X-axis labels (months)
         ];

         return response()->json($adminMatrix);
     }






}
