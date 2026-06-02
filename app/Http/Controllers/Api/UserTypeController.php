<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserTypeRequest;
use App\Http\Requests\UpdateUserTypeRequest;
use App\Models\UserType;
use App\Services\LockerUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserTypeController extends Controller
{
    public function __construct(private readonly LockerUserService $service) {}

    /** GET /api/user-types — list types visible to the actor. */
    public function index(Request $request): JsonResponse
    {
        $types = $this->service
            ->getAccessibleUserTypes($request->user())
            ->withCount('lockerUsers')
            ->get();

        return response()->json([
            'data'       => $types,
            'can_manage' => $request->user()->can('manage user types'),
            'badge_classes' => UserType::badgeClasses(),
        ]);
    }

    /** POST /api/user-types */
    public function store(StoreUserTypeRequest $request): JsonResponse
    {
        $type = $this->service->createUserType($request->validated(), $request->user());

        return response()->json([
            'data'    => $type->load('company'),
            'message' => 'User type created successfully.',
        ], 201);
    }

    /** PATCH /api/user-types/{userType} */
    public function update(UpdateUserTypeRequest $request, UserType $userType): JsonResponse
    {
        $this->service->updateUserType($userType, $request->validated(), $request->user());

        return response()->json([
            'data'    => $userType->fresh()->load('company'),
            'message' => 'User type updated successfully.',
        ]);
    }

    /** PATCH /api/user-types/{userType}/disable */
    public function disable(Request $request, UserType $userType): JsonResponse
    {
        $this->service->disableUserType($userType, $request->user());

        return response()->json(['message' => "User type '{$userType->name}' disabled."]);
    }

    /** PATCH /api/user-types/{userType}/enable */
    public function enable(Request $request, UserType $userType): JsonResponse
    {
        $this->service->enableUserType($userType, $request->user());

        return response()->json(['message' => "User type '{$userType->name}' enabled."]);
    }
}
