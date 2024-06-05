<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomePage extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tb_meeting_information';

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

    protected $primaryKey = 'meet_id';
    protected $fillable = [
        "book_id",
        "room_id",
        "room_name",
        "booker_name",
        "event",
        "meet_start_at",
        "meet_end_at",
        "seat_information",
        "template",
        "created_at",
        "updated_at",
    ];
}
