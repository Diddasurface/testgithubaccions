<?php

namespace Modules\Hotel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HotelFloorRequest extends FormRequest
{
	public function authorize()
	{
		return true;
	}

	public function rules()
	{
        $exist_description = $this->description ? true : false;

        $rules_description = $exist_description ? 
         [
                'required', 
                'max:50', 
                Rule::unique('tenant.hotel_floors', 'description')
                    ->where(function ($query){
                        $query->where('establishment_id', $this->establishment_id);
                    })
                    ->ignore($this->id),
        ] : [
            'nullable'
        ];

		$rules = [
            'description' => $rules_description,
            'active' => 'required|boolean',
            'establishment_id' => 'required|exists:tenant.establishments,id'
        ];

        return $rules;
	}

	protected function prepareForValidation()
    {
        $user = auth()->user();

        if ($user->type !== 'admin') {
            $this->merge([
                'establishment_id' => $user->establishment_id,
            ]);
        }
    }

}
