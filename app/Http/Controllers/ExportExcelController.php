<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Exports\UmrohExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportExcelController extends Controller
{
    public function ExportUmroh(Request $req)
    {
        $response = Excel::download(new UmrohExport($req->fromDt, $req->toDt), 'Umroh' . $req->fromDt . '~' . $req->toDt . '.xlsx');
        return $response->deleteFileAfterSend(true);
    }
}
