<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Appointment;
use App\Models\Pet;
use App\Models\EPrescription;

class AdminController extends Controller
{
    /**
     * Display the admin dashboard
     */
    public function dashboard()
    {
        $totalUsers = User::count();
        $totalAppointments = Appointment::count();
        $totalPets = Pet::count();
        $completedAppointments = Appointment::where('status', 'Completed')->count();
        
        $petOwners = User::where('role', 'Pet Owner')->count();
        $vets = User::where('role', 'Veterinarian')->count();
        $staff = User::where('role', 'Staff')->count();
        
        $uptimePercentage = 99.8;
        $aiAccuracy = 94.5;

        return view('admin.dashboard', compact('totalUsers', 'totalAppointments', 'totalPets', 'completedAppointments', 'petOwners', 'vets', 'staff', 'uptimePercentage', 'aiAccuracy'));
    }

    /**
     * Display user management
     */
    public function users()
    {
        $users = User::all();
        $roleStats = [
            'vet' => User::where('role', 'Veterinarian')->count(),
            'staff' => User::where('role', 'Staff')->count(),
            'owner' => User::where('role', 'Pet Owner')->count(),
        ];

        return view('admin.users', compact('users', 'roleStats'));
    }

    /**
     * Display analytics
     */
    public function analytics()
    {
        $appointmentStats = Appointment::count();
        $prescriptionStats = EPrescription::count();
        $userStats = User::count();

        return view('admin.analytics', compact('appointmentStats', 'prescriptionStats', 'userStats'));
    }

    /**
     * Update a user
     */
    public function updateUser($id)
    {
        $user = User::findOrFail($id);
        
        // Don't allow deletion of the current super admin
        if ($user->isSuperAdmin()) {
            return response()->json(['error' => 'Cannot edit super admin user'], 403);
        }

        $request = request();
        if ($request->filled('name') && !$request->filled('full_name')) {
            $request->merge(['full_name' => $request->input('name')]);
        }
        if ($request->filled('phone') && !$request->filled('contact_no')) {
            $request->merge(['contact_no' => $request->input('phone')]);
        }
        if ($request->filled('role')) {
            $roleMap = [
                'owner' => 'Pet Owner',
                'pet owner' => 'Pet Owner',
                'vet' => 'Veterinarian',
                'veterinarian' => 'Veterinarian',
                'staff' => 'Staff',
            ];
            $normalized = strtolower($request->input('role'));
            $request->merge(['role' => $roleMap[$normalized] ?? $request->input('role')]);
        }

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->user_id . ',user_id',
            'contact_no' => 'nullable|string|max:20',
            'role' => 'required|in:Pet Owner,Veterinarian,Staff',
        ]);

        $user->update($validated);

        return response()->json(['success' => true, 'message' => 'User updated successfully']);
    }

    /**
     * Delete a user
     */
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        
        // Don't allow deletion of the current super admin
        if ($user->isSuperAdmin()) {
            return response()->json(['error' => 'Cannot delete super admin user'], 403);
        }

        // Don't allow deleting yourself
        if ($user->user_id === auth()->user()->user_id) {
            return response()->json(['error' => 'Cannot delete your own account'], 403);
        }

        $user->delete();

        return response()->json(['success' => true, 'message' => 'User deleted successfully']);
    }

    /**
     * Store a new user
     */
    public function storeUser()
    {
        $request = request();
        if ($request->filled('name') && !$request->filled('full_name')) {
            $request->merge(['full_name' => $request->input('name')]);
        }
        if ($request->filled('phone') && !$request->filled('contact_no')) {
            $request->merge(['contact_no' => $request->input('phone')]);
        }
        if ($request->filled('role')) {
            $roleMap = [
                'owner' => 'Pet Owner',
                'pet owner' => 'Pet Owner',
                'vet' => 'Veterinarian',
                'veterinarian' => 'Veterinarian',
                'staff' => 'Staff',
            ];
            $normalized = strtolower($request->input('role'));
            $request->merge(['role' => $roleMap[$normalized] ?? $request->input('role')]);
        }

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'contact_no' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
            'role' => 'required|in:Pet Owner,Veterinarian,Staff',
        ]);

        $validated['password'] = bcrypt($validated['password']);
        $validated['status'] = 'Active';

        User::create($validated);

        return response()->json(['success' => true, 'message' => 'User created successfully']);
    }

    /**
     * Display all pets with filter by owner
     */
    public function pets()
    {
        $ownerId = request()->query('owner_id');
        
        $query = Pet::with('owner');
        
        if ($ownerId) {
            $query->where('user_id', $ownerId);
        }
        
        $pets = $query->paginate(20);
        $owners = User::where('role', 'Pet Owner')->get();

        return view('admin.pets', compact('pets', 'owners', 'ownerId'));
    }
}
