<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SavedTilawa extends Model {
    protected $fillable=['user_id','tilawa_id'];
    public function user()   { return $this->belongsTo(User::class); }
    public function tilawa() { return $this->belongsTo(Tilawa::class); }
}
