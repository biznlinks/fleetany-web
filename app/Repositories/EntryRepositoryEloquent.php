<?php

namespace App\Repositories;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\EntryRepository;
use App\Entities\Entry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;

class EntryRepositoryEloquent extends BaseRepository implements EntryRepository
{

    protected $rules = [
        'entry_type_id'   => 'required',
        'datetime_ini'  => 'date|date_format:Y-m-d H:i:s|required',
        'datetime_end' => 'date|date_format:Y-m-d H:i:s|after:datetime_ini',
        'cost'      => 'min:0.01|numeric|required',
        ];

    public function model()
    {
        return Entry::class;
    }

    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }
    
    public function results($filters = [])
    {
        $filters = $this->formatFilters($filters);
        
        $entries = $this->scopeQuery(function ($query) use ($filters) {

            $query = $query->select(
                'entries.*',
                'entries.datetime_ini as datetime-ini',
                'models.name as vehicle',
                'types.name as entry-type'
            );
            $query = $query->leftJoin('vehicles', 'entries.vehicle_id', '=', 'vehicles.id');
            $query = $query->leftJoin('models', 'vehicles.model_vehicle_id', '=', 'models.id');
            $query = $query->leftJoin('types', 'entries.entry_type_id', '=', 'types.id');
            
            if (!empty($filters['vehicle-id'])) {
                $query = $query->where('models.name', 'like', '%'.$filters['vehicle-id'].'%');
            }
            if (!empty($filters['entry-type-id'])) {
                $query = $query->where('types.name', 'like', '%'.$filters['entry-type-id'].'%');
            }
            if (!empty($filters['datetime-ini'])) {
                $query = $query->where('entries.datetime_ini', $filters['datetime-ini']);
            }
            if (!empty($filters['cost'])) {
                $query = $query->where('entries.cost', $filters['cost']);
            }

            $query = $query->where('entries.company_id', Auth::user()['company_id']);
            if ($filters['sort'] == 'vehicle') {
                $sort = 'models.name';
            } elseif ($filters['sort'] == 'entry_type') {
                $sort = 'types.name';
            } else {
                $sort = 'entries.'.$filters['sort'];
            }
            $query = $query->orderBy($sort, $filters['order']);
            
            return $query;
        })->paginate($filters['paginate']);
        
        return $entries;
    }
    
    private function formatFilters($filters = [])
    {
        $filters['datetime-ini'] = empty($filters['datetime-ini']) ? "" :
                                    HelperRepository::date($filters['datetime-ini']);
    
        return $filters;
    }
    
    public function getInputs($inputs)
    {
        $inputs['datetime_ini'] = HelperRepository::date($inputs['datetime_ini'], App::getLocale());
        $inputs['datetime_end'] = HelperRepository::date($inputs['datetime_end'], App::getLocale());
        $inputs['cost'] = HelperRepository::money($inputs['cost'], App::getLocale());
        return $inputs;
    }
    
    public function setInputs($inputs)
    {
        $inputs['entity_key'] = "entry";
        if (empty($inputs['vendor_id'])) {
            unset($inputs['vendor_id']);
        }
        $inputs['datetime_ini'] = HelperRepository::date($inputs['datetime_ini']);
        $inputs['datetime_end'] = HelperRepository::date($inputs['datetime_end']);
        $inputs['cost'] = HelperRepository::money($inputs['cost']);
        return $inputs;
    }
    
    public static function getServiceCostMonthStatistics($month, $year)
    {
        $prefix = \DB::getTablePrefix();

        $cost = Entry::join('types', 'types.id', '=', 'entries.entry_type_id')
                ->whereRaw('MONTH('.$prefix.'entries.datetime_ini) = ?', [$month])
                ->whereRaw('YEAR('.$prefix.'entries.datetime_ini) = ?', [$year])
                ->where('types.name', 'service')
                ->where('entries.company_id', Auth::user()['company_id'])
                ->sum('cost');
        
        return $cost;
    }
    
    public static function getLastsServiceCostStatistics()
    {
        $statistics = [];
        $date = Carbon::now()->addMonthNoOverflow();
        for ($i = 0; $i < 6; $i++) {
            $date = $date->subMonthNoOverflow();
            $statistics[$date->month] = self::getServiceCostMonthStatistics($date->month, $date->year);
        }
        
        return array_reverse($statistics, true);
    }
    
    public static function getServicesStatistics()
    {
        $services['in_progress']['color'] = '#3871cf';
        $services['in_progress']['result'] = Entry::join('types', 'types.id', '=', 'entries.entry_type_id')
                ->where('entries.company_id', Auth::user()['company_id'])
                ->where('types.entity_key', 'entry')
                ->where('types.name', 'service')
                ->where('entries.datetime_ini', '<=', Carbon::now())
                ->where(function ($query) {
                    $query->where('entries.datetime_end', '>', Carbon::now())
                          ->orWhereNull('entries.datetime_end');
                })
                ->count();
                
        $services['foreseen']['color'] = '#cf7138';
        $services['foreseen']['result'] = Entry::join('types', 'types.id', '=', 'entries.entry_type_id')
                ->where('entries.company_id', Auth::user()['company_id'])
                ->where('types.entity_key', 'entry')
                ->where('types.name', 'service')
                ->where('entries.datetime_ini', '>', Carbon::now())
                ->count();
           
        $services['accomplished']['color'] = '#38cf71';
        $services['accomplished']['result'] = Entry::join('types', 'types.id', '=', 'entries.entry_type_id')
                ->where('entries.company_id', Auth::user()['company_id'])
                ->where('types.entity_key', 'entry')
                ->where('types.name', 'service')
                ->where('entries.datetime_end', '<=', Carbon::now())->count();
                
                
        return $services;
    }
}
