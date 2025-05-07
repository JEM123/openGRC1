<x-filament::page>
    <div class="mb-6">
        <h1 class="text-2xl font-bold">{{ $record->title }}</h1>
        <p class="text-gray-600">{{ $record->description }}</p>
    </div>

    <form id="checklist-response-form" method="POST" action="{{ route('checklists.responses.store', $record->id) }}">
        @csrf
        <table class="min-w-full bg-white border">
            <thead>
                <tr>
                    <th class="px-4 py-2 border">Item</th>
                    <th class="px-4 py-2 border">Type</th>
                    <th class="px-4 py-2 border">Response</th>
                    <th class="px-4 py-2 border">Notes</th>
                    <th class="px-4 py-2 border">Activity</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($record->checklist_responses as $response)
                    <tr x-data="{
                        value: @js($response->type === 'checkbox' ? ($response->response === '1') : $response->response),
                        originalValue: @js($response->type === 'checkbox' ? ($response->response === '1') : $response->response),
                        notes: @js($response->notes),
                        originalNotes: @js($response->notes),
                        saving: false,
                        notesSaving: false,
                        activity: {
                            updated_at: '{{ ($response->updated_at && $response->updated_at->ne($response->created_at)) ? $response->updated_at->format('Y-m-d H:i') : '' }}',
                            updated_by: '{{ $response->updatedBy?->name ?? '' }}'
                        },
                        saveResponse() {
                            if (this.value === this.originalValue) return;
                            this.saving = true;
                            let payload = {};
                            if ('{{ $response->type }}' === 'checkbox') {
                                payload.responses = { '{{ $response->order }}': this.value ? '1' : '0' };
                            } else {
                                payload.responses = { '{{ $response->order }}': this.value };
                            }
                            fetch('{{ route('checklists.responses.store', $record->id) }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify(payload)
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.activity && data.activity['{{ $response->order }}']) {
                                    this.activity = data.activity['{{ $response->order }}'];
                                }
                                this.originalValue = this.value;
                                this.saving = false;
                            });
                        },
                        saveNotes() {
                            if (this.notes === this.originalNotes) return;
                            this.notesSaving = true;
                            fetch('{{ route('checklists.responses.store', $record->id) }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    notes: { '{{ $response->order }}': this.notes }
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.activity && data.activity['{{ $response->order }}']) {
                                    this.activity = data.activity['{{ $response->order }}'];
                                }
                                this.originalNotes = this.notes;
                                this.notesSaving = false;
                            });
                        }
                    }">
                        <td class="border px-4 py-2">{{ $response->title }}</td>
                        <td class="border px-4 py-2">{{ ucfirst($response->type) }}</td>
                        <td class="border px-4 py-2">
                            @if ($response->type === 'checkbox')
                                <input type="checkbox" x-model="value" @change="saveResponse" />
                                <span x-show="saving" x-cloak class="ml-2 text-xs text-gray-500">Saving...</span>
                            @elseif ($response->type === 'select')
                                <select x-model="value" @change="saveResponse">
                                    @foreach (explode(',', $response->options ?? '') as $option)
                                        @if (trim($option) !== '')
                                            <option value="{{ trim($option) }}">{{ trim($option) }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                <span x-show="saving" x-cloak class="ml-2 text-xs text-gray-500">Saving...</span>
                                @elseif ($response->type === 'accessreview')
                                <select x-model="value" @change="saveResponse">
                                    <option value="Not Reviewed">Not Reviewed</option>
                                    <option value="User Verified">User Verified</option>
                                    <option value="User & Role Verified">User & Role Verified</option>
                                    <option value="Verified with Issues">Verified with Issues</option>
                                    <option value="Verified with Issues Remediated">Verified with Issues Remediated</option>
                                </select>
                                <span x-show="saving" x-cloak class="ml-2 text-xs text-gray-500">Saving...</span>

                                @elseif ($response->type === 'number')
                                <input type="number" x-model="value" @blur="saveResponse" class="border rounded px-2 py-1" />
                                <span x-show="saving" x-cloak class="ml-2 text-xs text-gray-500">Saving...</span>
                            @elseif ($response->type === 'text')
                                <textarea x-model="value" @blur="saveResponse" class="border rounded px-2 py-1"></textarea>
                                <span x-show="saving" x-cloak class="ml-2 text-xs text-gray-500">Saving...</span>
                            @endif
                        </td>
                        <td class="border px-4 py-2">
                            <textarea x-model="notes" @blur="saveNotes" class="border rounded px-2 py-1 w-full"></textarea>
                            <span x-show="notesSaving" x-cloak class="ml-2 text-xs text-gray-500">Saving...</span>
                        </td>
                        <td class="border px-4 py-2 text-xs text-gray-500">
                            <template x-if="activity.updated_at">
                                <span x-text="activity.updated_at"></span> by 
                            </template>
                            
                            <template x-if="activity.updated_by">
                                by <span class="text-gray-700" x-text="activity.updated_by"></span>
                            </template>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </form>
</x-filament::page> 