<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DownloadLog extends Model {
    protected $table='downloads_log';
    public $timestamps=false;
    protected $fillable=['user_id','tilawa_id','ip_address','downloaded_at'];
}
