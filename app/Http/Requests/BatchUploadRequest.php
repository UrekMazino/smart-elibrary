<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BatchUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'batch_file'         => 'required|file|mimes:mrc|max:40960',
            'subject'            => 'required|string|max:255',
            // The default type: every record whose mapped value is missing/unmapped gets it.
            'material_type_id'   => 'required|integer|exists:materials,id',
            'material_map_field' => 'nullable|string|max:20',
            'material_map'       => 'nullable|json',
        ];
    }

    public function messages()
    {
        return [
            'material_type_id.required' => 'Select the default Material Type.',
            'subject.required'          => 'Enter the subject of this batch.',
        ];
    }
}
