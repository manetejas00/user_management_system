<?php

namespace App\Http\Controllers;

use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PersonController extends Controller
{
    // List all persons
    public function index()
    {
        try {
            logger("Fetching all persons.");
            $persons = Person::whereNull('deleted_at')->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Persons fetched successfully',
                'data' => $persons
            ], 200);
        } catch (\Exception $e) {
            logger("Error fetching persons: " . $e->getMessage());
            return response()->json(['message' => 'An error occurred while fetching persons'], 500);
        }
    }

    // Delete a single user
    public function destroy($id)
    {
        try {
            logger("Deleting person with ID: " . $id);

            $validator = Validator::make(['id' => $id], [
                'id' => 'required|exists:persons,id',
            ]);

            if ($validator->fails()) {
                logger("Validation failed for delete: " . json_encode($validator->errors()));
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $person = Person::findOrFail($id);
            $person->delete();

            logger("Person with ID: $id deleted successfully.");
            return response()->json(['message' => 'User deleted successfully!']);
        } catch (\Exception $e) {
            logger("Error deleting person with ID: $id - " . $e->getMessage());
            return response()->json(['message' => 'An error occurred while deleting the user'], 500);
        }
    }

    // Bulk delete users
    public function bulkDelete(Request $request)
    {
        try {
            logger("Bulk deleting persons with IDs: " . json_encode($request->ids));

            $validator = Validator::make($request->all(), [
                'ids' => 'required|array|min:1',
                'ids.*' => 'exists:persons,id',
            ]);

            if ($validator->fails()) {
                logger("Validation failed for bulk delete: " . json_encode($validator->errors()));
                return response()->json(['errors' => $validator->errors()], 422);
            }

            Person::whereIn('id', $request->ids)->delete();

            logger("Bulk delete successful for persons: " . json_encode($request->ids));
            return response()->json(['message' => 'Users deleted successfully!']);
        } catch (\Exception $e) {
            logger("Error in bulk delete: " . $e->getMessage());
            return response()->json(['message' => 'An error occurred while deleting users'], 500);
        }
    }

    // Bulk create users
    public function bulkCreate(Request $request)
    {
        try {
            logger("Bulk creating persons: " . json_encode($request->input('users')));

            $validator = Validator::make($request->all(), [
                'users' => 'required|array|min:1',
                'users.*.name' => 'required|string|max:255',
                'users.*.email' => 'required|email|unique:persons,email',
                'users.*.role' => 'required|string|in:Project Manager,Team Lead,Developer',
            ]);

            if ($validator->fails()) {
                logger("Validation failed for bulk create: " . json_encode($validator->errors()));
                return response()->json(['errors' => $validator->errors()], 422);
            }

            foreach ($request->users as $user) {
                Person::create([
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                ]);
            }

            logger("Bulk create successful.");
            return response()->json(['message' => 'Users added successfully']);
        } catch (\Exception $e) {
            logger("Error in bulk create: " . $e->getMessage());
            return response()->json(['message' => 'An error occurred while adding users'], 500);
        }
    }

    // Update a user
    public function update(Request $request, $id)
    {
        try {
            logger("Updating person with ID: " . $id);

            $validator = Validator::make(array_merge($request->all(), ['id' => $id]), [
                'id' => 'required|exists:persons,id',
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:persons,email,' . $id,
                'role' => 'required|string|in:Project Manager,Team Lead,Developer',
            ]);

            if ($validator->fails()) {
                logger("Validation failed for update: " . json_encode($validator->errors()));
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $person = Person::findOrFail($id);
            $person->update([
                'name' => $request->name,
                'email' => $request->email,
                'role' => $request->role,
            ]);

            logger("Person with ID: $id updated successfully.");
            return response()->json(['message' => 'Person updated successfully', 'person' => $person]);
        } catch (\Exception $e) {
            logger("Error updating person with ID: $id - " . $e->getMessage());
            return response()->json(['message' => 'An error occurred while updating the person'], 500);
        }
    }
}
