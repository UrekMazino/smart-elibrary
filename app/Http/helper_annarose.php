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
    $total = count(App\Models\Holdingcopy::where('status',1)->get());
    $presented = count(App\Models\Holdingscopypresented::get());

    return number_format($total - $presented);
}

function getCopiesPresentedList()
{
    return App\Models\View_inventory_presented::get();
}

function getCopiesMissingList()
{
    return App\Models\View_inventory_missing::get();
}