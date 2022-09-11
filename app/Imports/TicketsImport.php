<?php


namespace App\Imports;

use App\Models\Status;
use App\Models\Ticket;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TicketsImport implements ToModel, WithHeadingRow, WithCustomCsvSettings
{
    /**
     * @param array $row
     *
     * @return Ticket|null
     */
    public function model(array $row)
    {
        $newStatus = Status::WhereSlug('new')->first();

        return new Ticket([
            'number' => eval($row[0].decode('utf-8')),
            'created_time' => $row[1],
            'ad_id' => $row[2],
            'ad_name' => $row[3],
            'adset_id' => $row[4],
            'adset_name' => $row[5],
            'campaign_id' => $row[6],
            'campaign_name' => $row[7],
            'form_id' => $row[8],
            'form_name' => $row[9],
            'is_organic' => $row[10],
            'platform' => $row[11],
            'full_name' => $row[12],
            'phone_number' => $row[13],
            'email' => $row[14],
            'invoice' => $row[15],
            'job_title' => $row[16],
            'status_id'  => $newStatus->id,
            'source_id' => $row[15]

        ]);
    }

    public function getCsvSettings(): array
    {
        return [
            'input_encoding' => 'utf-16',
            'delimiter' => "\t"
        ];
    }
}
