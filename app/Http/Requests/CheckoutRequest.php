<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class CheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            
            'shipping_address' => ['required', 'array'],
            'shipping_address.name' => ['required', 'string', 'max:255'],
            'shipping_address.phone' => ['required', 'string', 'max:20'],
            'shipping_address.address' => ['required', 'string'],
            'shipping_address.city' => ['required', 'string', 'max:255'],
            'shipping_address.postal_code' => ['required', 'string', 'max:10'],
            'shipping_address.province' => ['required', 'string', 'max:255'],
            'shipping_address.country' => ['required', 'string', 'max:255'],
            
            'payment_method' => ['nullable', 'string', 'in:cash_on_delivery,bank_transfer,credit_card,e_wallet'],
            'shipping_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
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
            'items.required' => 'At least one item is required for checkout',
            'items.min' => 'At least one item is required for checkout',
            'items.*.product_id.required' => 'Product ID is required for each item',
            'items.*.product_id.exists' => 'Selected product does not exist',
            'items.*.quantity.required' => 'Quantity is required for each item',
            'items.*.quantity.min' => 'Quantity must be at least 1',
            
            'shipping_address.required' => 'Shipping address is required',
            'shipping_address.name.required' => 'Recipient name is required',
            'shipping_address.phone.required' => 'Phone number is required',
            'shipping_address.address.required' => 'Street address is required',
            'shipping_address.city.required' => 'City is required',
            'shipping_address.postal_code.required' => 'Postal code is required',
            'shipping_address.province.required' => 'Province is required',
            'shipping_address.country.required' => 'Country is required',
            
            'payment_method.in' => 'Invalid payment method selected',
            'shipping_amount.numeric' => 'Shipping amount must be a valid number',
            'shipping_amount.min' => 'Shipping amount cannot be negative',
            'notes.max' => 'Notes cannot exceed 1000 characters',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}