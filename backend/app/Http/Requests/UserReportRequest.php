<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'user';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string|min:10|max:5000',
            'category_id' => 'required|exists:violence_categories,id',
            'anonim' => 'sometimes|boolean',
            'incident_date' => 'sometimes|nullable|date|before_or_equal:today',
            'incident_location' => 'sometimes|nullable|string|max:255',
            'perpetrator_name' => 'sometimes|nullable|string|max:255',
            'perpetrator_relationship' => 'sometimes|nullable|string|max:100',
            'witnesses' => 'sometimes|nullable|string|max:1000',
            'evidence_description' => 'sometimes|nullable|string|max:2000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Title is required.',
            'title.max' => 'Title cannot exceed 255 characters.',
            'description.required' => 'Description is required.',
            'description.min' => 'Description must be at least 10 characters.',
            'description.max' => 'Description cannot exceed 5000 characters.',
            'category_id.required' => 'Category is required.',
            'category_id.exists' => 'Selected category is invalid.',
            'anonim.boolean' => 'Anonymous field must be true or false.',
            'incident_date.date' => 'Incident date must be a valid date.',
            'incident_date.before_or_equal' => 'Incident date cannot be in the future.',
            'incident_location.max' => 'Location cannot exceed 255 characters.',
            'perpetrator_name.max' => 'Perpetrator name cannot exceed 255 characters.',
            'perpetrator_relationship.max' => 'Relationship cannot exceed 100 characters.',
            'witnesses.max' => 'Witness information cannot exceed 1000 characters.',
            'evidence_description.max' => 'Evidence description cannot exceed 2000 characters.',
        ];
    }
}
