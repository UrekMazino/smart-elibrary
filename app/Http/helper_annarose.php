<?php
function getRegion()
{  
    return App\Models\Region::get();
}

function getPatronType()
{  
    return App\Models\Patrontype::get();
}

function getPatronInfo($id,$column)
{
    $user = App\Models\User::where('id',$id)->first();
    if(!isset($user[$column]))
        return "";
    else
        return $user[$column];
}

function ifExist($data)
{
    if(isset($data))
        return $data;
    else
        return null;
}

function checkStocks($catalog_id)
{
    $ctr = App\Models\Holdingcopy::where('catalog_id', $catalog_id)->where('status', 1)->count();

    return $ctr > 0 ? true : false;
}

// Total physical copies on record for a material.
function countCopies($catalog_id)
{
    return App\Models\Holdingcopy::where('catalog_id', $catalog_id)->count();
}

// Physical copies currently available to borrow (status 1 = Available in copies_status).
function countAvailableCopies($catalog_id)
{
    return App\Models\Holdingcopy::where('catalog_id', $catalog_id)->where('status', 1)->count();
}

function mostDownloaded()
{
    return App\Models\View_most_downloaded::get();
}
function mostViewed()
{
    return App\Models\View_most_viewed::get();
}

function getAllCopies()
{
    return count(App\Models\Holdingcopy::where('status',1)->get());
}

function getCopiesPresented()
{
    return number_format(count(App\Models\Holdingscopypresented::get()));
}

function getCopiesMissing()
{
    return number_format(copiesMissingQuery()->count());
}

// Copies scanned in the current inventory count, with the record's accession number and title.
// (These lists used to read view_inventory_presented / view_inventory_missing, which were never
// created in the database, so both report pages failed.)
function getCopiesPresentedList()
{
    return DB::table('holdingscopypresenteds as p')
        ->leftJoin('copies as c', 'c.circulation_number', '=', 'p.circulation_number')
        ->leftJoin('catalogs as k', 'k.id', '=', 'p.catalog_id')
        ->orderBy('p.created_at')
        ->get(['p.id', 'p.catalog_id', 'k.accession_number', 'p.circulation_number', 'c.title', 'p.created_at']);
}

// Available copies not scanned yet in the current count.
function getCopiesMissingList()
{
    return copiesMissingQuery()
        ->leftJoin('catalogs as k', 'k.id', '=', 'c.catalog_id')
        ->orderBy('c.circulation_number')
        ->get(['c.id', 'c.catalog_id', 'k.accession_number', 'c.circulation_number', 'c.title']);
}

function copiesMissingQuery()
{
    return DB::table('copies as c')
        ->where('c.status', 1)
        ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('holdingscopypresenteds as p')
            ->whereColumn('p.circulation_number', 'c.circulation_number'));
}