<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertChatbotFaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $faqId = $this->route('faq')?->id;

        return [
            'slug'        => [
                'required',
                'string',
                'alpha_dash',
                'max:80',
                Rule::unique('chatbot_faqs', 'slug')
                    ->where('locale', $this->input('locale', 'en'))
                    ->ignore($faqId),
            ],
            'locale'      => ['required', 'in:en,tl'],
            'persona'     => ['required', 'in:both,dr_leo,nurse_mika'],
            'question'    => ['required', 'string', 'max:255'],
            'answer_html' => ['required', 'string', 'max:2000'],
            'keywords'    => ['nullable', 'array', 'max:15'],
            'keywords.*'  => ['string', 'max:40'],
            'follow_ups'  => ['nullable', 'array', 'max:3'],
            'follow_ups.*'=> ['string', 'max:160'],
            'sort_order'  => ['sometimes', 'integer', 'min:0', 'max:999'],
            'is_active'   => ['sometimes', 'boolean'],
        ];
    }
}
