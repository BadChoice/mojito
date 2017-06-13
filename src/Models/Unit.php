<?php namespace BadChoice\Mojito\Models;

use BadChoice\Mojito\Exceptions\UnitsNotCompatibleException;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model{

    use SoftDeletes;

    protected $table    = "units";
    protected $guarded  = ['id'];

    const STANDARD  = 1;
    const KG        = 2;
    const L         = 3;
    const LBS       = 4;
    const GAL       = 5;

    //====================================================================
    // SCOPES
    //====================================================================
    public function scopeByMainUnit($query,$mainUnit){
        $query->where('main_unit','=',$mainUnit);
    }

    public static function unitFor($mainUnit,$conversion,$shouldCreate = false){
        $unit = Unit::where('main_unit','=',$mainUnit)->where('conversion','=',$conversion)->first();
        if($unit == null && $shouldCreate){
            $unit = Unit::create(['main_unit' => $mainUnit, 'conversion' => $conversion , 'name' => $conversion . ' ' . Unit::getMainUnitName($mainUnit)]);
        }
        return $unit;
    }

    //====================================================================
    // METHODS
    //====================================================================
    public static function convert($qty,$originUnitId,$destinationUnitId){
        if($originUnitId == null || $destinationUnitId == null) return $qty;    //To keep keep retrofunctionality (can be removed when everybody on >1.9)
        $originUnit         = static::find($originUnitId);
        $destinationUnit    = static::find($destinationUnitId);

        if (! static::areCompatible($originUnit, $destinationUnit) ){
            throw new UnitsNotCompatibleException;
        }

        $originMainQty = $qty           * $originUnit     ->conversion;
        $finalQty      = $originMainQty / $destinationUnit->conversion;

        return $finalQty;
    }

    public static function areCompatible($origin, $destination){
        return $origin->main_unit == $destination->main_unit;
    }

    public function convertToMainUnit($qty){
        return $qty * $this->conversion;
    }

    public function mainUnitName(){
        return static::getMainUnitName($this->main_unit);
    }

    public static function getMainUnits(){
        return [
            static::STANDARD    => static::getMainUnitName(static::STANDARD),
            static::KG          => static::getMainUnitName(static::KG),
            static::L           => static::getMainUnitName(static::L),
            static::LBS         => static::getMainUnitName(static::LBS),
            static::GAL         => static::getMainUnitName(static::GAL),
        ];
    }

    public static function getMainUnitName($mainUnit){
        if      ($mainUnit == static::STANDARD) return __("admin.standard");
        else if ($mainUnit == static::KG)       return "KG";
        else if ($mainUnit == static::L)        return "L";
        else if ($mainUnit == static::LBS)      return "LBS";
        else if ($mainUnit == static::GAL)      return "GAL";
    }
}