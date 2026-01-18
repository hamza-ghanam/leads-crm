<?php

namespace App\Services\Meta;

use Illuminate\Support\Arr;

class MetaLeadNormalizer
{
    /**
     * يحول field_data (array) إلى answers key=>value
     * Meta usually returns:
     * field_data: [{name: "full_name", values:["X"]}, ...]
     */
    public function normalizeAnswers(array $graphPayload): array
    {
        $fieldData = Arr::get($graphPayload, 'field_data', []);
        $answers = [];

        foreach ($fieldData as $item) {
            $name = Arr::get($item, 'name');
            $values = Arr::get($item, 'values', []);

            if (!$name) continue;

            // store the value: if one value store string، if more store array
            $answers[$name] = count($values) <= 1 ? ($values[0] ?? null) : $values;
        }

        $clean = [];
        foreach ($answers as $k => $v) {
            $ck = trim(mb_strtolower((string)$k));
            $clean[$ck] = $v;
        }

        return $clean;
    }

    /**
     * استخرج أهم 3 حقول لو متوفرين بأسماء مختلفة حسب الفورم
     */
    public function extractCoreFields(array $answers): array
    {
        $fullName = $this->firstByKeys($answers, [
            'full_name','fullname','name','full name',
        ]);

        $phone = $this->firstByKeys($answers, [
            'phone_number','phone','mobile','mobile_number','contact_number',
        ]);

        $email = $this->firstByKeys($answers, [
            'email','email_address','e-mail',
        ]);

        return [
            'full_name' => $fullName,
            'phone' => $this->cleanupPhone($phone),
            'email' => $email ? trim((string)$email) : null,
        ];
    }

    private function firstByKeys(array $answers, array $keys): mixed
    {
        foreach ($keys as $k) {
            $k = trim(mb_strtolower($k));
            if (array_key_exists($k, $answers) && $answers[$k] !== null && $answers[$k] !== '') {
                return $answers[$k];
            }
        }
        return null;
    }

    private function cleanupPhone(mixed $phone): ?string
    {
        if (!$phone) return null;
        $s = trim((string)$phone);
        $s = str_replace([' ', '-', '(', ')'], '', $s);
        return $s ?: null;
    }
}
