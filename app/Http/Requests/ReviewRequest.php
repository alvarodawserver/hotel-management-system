<?php

namespace App\Http\Requests;

use App\Models\Reservation;
use App\Models\Review;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Writes or edits the guest's review of a stay.
 */
class ReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        if ($this->isMethod('POST')) {
            return Gate::inspect('create', [Review::class, $this->reservation()]);
        }

        // A review removed by an admin is soft deleted, so it is not found.
        $review = $this->reservation()->review;

        return $review !== null
            ? Gate::inspect('update', $review)
            : Response::deny(__('This review can no longer be changed.'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:'.Review::MIN_COMMENT_LENGTH, 'max:'.Review::MAX_COMMENT_LENGTH],
        ];
    }

    public function reservation(): Reservation
    {
        /** @var Reservation */
        return $this->route('reservation');
    }
}
