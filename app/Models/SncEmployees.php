<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SncEmployees extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tb_snc_employees';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the primary key.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    // protected $primaryKey = '_id';
    protected $fillable = [
        "person_code",
        "person_card",
        "prior_name",
        "first_name_th",
        "last_name_th",
        "first_name_en",
        "last_name_en",
        "department",
        "section",
        "employee_level_code",
        "employee_level_name",
        "position_code",
        "position_name",
        "employee_type",
        "employee_contract_type",
        "contract_start",
        "contract_end",
        "employee_level",
        "company_id",
        "company_name",
    ];
}
