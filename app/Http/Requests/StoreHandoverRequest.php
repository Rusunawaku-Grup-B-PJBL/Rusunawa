<?php

namespace App\Http\Requests;

class StoreHandoverRequest extends ApiFormRequest
{
    public const ITEMS = [
        'Kunci kamar',
        'Kasur & bantal',
        'Lemari',
        'AC',
        'Lampu',
        'Kran & kamar mandi',
    ];

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:checkin,checkout'],
            'notes' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:4096'],
            'signature' => ['required', 'file', 'mimes:png', 'max:2048'],
            'items' => ['required', 'array', 'size:6'],
            'items.*.item_name' => ['required', 'string', 'distinct', 'in:'.implode(',', self::ITEMS)],
            'items.*.condition' => ['required', 'in:baik,rusak'],
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'jenis serah terima',
            'notes' => 'catatan',
            'photo' => 'foto',
            'signature' => 'tanda tangan',
            'items' => 'daftar barang',
            'items.*.item_name' => 'nama barang',
            'items.*.condition' => 'kondisi barang',
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'signature.mimes' => 'Tanda tangan harus berupa berkas PNG.',
            'signature.max' => 'Ukuran tanda tangan maksimal 2 MB.',
            'items.size' => 'Daftar barang harus berisi tepat 6 item.',
            'items.*.item_name.distinct' => 'Setiap item barang hanya boleh dicantumkan satu kali.',
        ]);
    }
}
