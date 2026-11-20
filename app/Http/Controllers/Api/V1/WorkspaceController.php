<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateWorkspace;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreWorkspaceRequest;
use App\Http\Requests\Api\UpdateWorkspaceRequest;
use App\Http\Resources\WorkspaceResource;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class WorkspaceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return WorkspaceResource::collection(
            $request->user()->workspaces()->orderBy('workspaces.name')->get()
        );
    }

    public function store(StoreWorkspaceRequest $request, CreateWorkspace $createWorkspace): WorkspaceResource
    {
        $workspace = $createWorkspace->handle($request->user(), $request->validated());

        return new WorkspaceResource($workspace);
    }

    public function show(Workspace $workspace): WorkspaceResource
    {
        Gate::authorize('view', $workspace);

        return new WorkspaceResource($workspace);
    }

    public function update(UpdateWorkspaceRequest $request, Workspace $workspace): WorkspaceResource
    {
        $workspace->update($request->validated());

        return new WorkspaceResource($workspace);
    }

    public function destroy(Workspace $workspace): Response
    {
        Gate::authorize('delete', $workspace);

        $workspace->delete();

        return response()->noContent();
    }
}
