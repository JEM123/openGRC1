<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Checklist;
use App\Models\ChecklistResponse;

class ChecklistResponseController extends Controller
{
    public function store(Request $request, Checklist $checklist)
    {
        $updatedResponses = [];

        foreach ($request->input('responses', []) as $itemId => $value) {
            $data = [
                'response' => $value,
                'updated_by' => auth()->id(),
            ];
            if ($notes = $request->input('notes')[$itemId] ?? null) {
                $data['notes'] = $notes;
            }
            $response = ChecklistResponse::updateOrCreate(
                [
                    'checklist_id' => $checklist->id,
                    'user_id' => auth()->id(),
                    'order' => $itemId,
                ],
                $data
            );
            $response->refresh()->load('updatedBy');
            $updatedResponses[$itemId] = [
                'updated_at' => $response->updated_at->ne($response->created_at) ? $response->updated_at->format('Y-m-d H:i') : '',
                'updated_by' => $response->updatedBy?->name ?? '',
            ];
        }
        // Also handle notes-only updates
        foreach ($request->input('notes', []) as $itemId => $notes) {
            if (!isset($request->input('responses', [])[$itemId])) {
                $response = ChecklistResponse::updateOrCreate(
                    [
                        'checklist_id' => $checklist->id,
                        'user_id' => auth()->id(),
                        'order' => $itemId,
                    ],
                    [
                        'notes' => $notes,
                        'updated_by' => auth()->id(),
                    ]
                );
                $response->refresh()->load('updatedBy');
                $updatedResponses[$itemId] = [
                    'updated_at' => $response->updated_at->ne($response->created_at) ? $response->updated_at->format('Y-m-d H:i') : '',
                    'updated_by' => $response->updatedBy?->name ?? '',
                ];
            }
        }
        if ($request->expectsJson()) {
            return response()->json(['activity' => $updatedResponses]);
        }
        return back()->with('success', 'Responses saved!');
    }
} 