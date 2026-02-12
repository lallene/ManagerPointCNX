<?php

namespace App\Imports;


use App\Models\Projet;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class ProjetsImport implements ToModel, WithBatchInserts, WithChunkReading, WithHeadingRow, SkipsOnError
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        //dd($row);
        $site= DB::table('sites')->where('designation', $row['site'])->first();
        if ($site == null ){ $row['site'] = 1;}else{ $row['site'] = $site->id;};

        $projet = DB::table('projets')->where('designation', $row['projetservice'])->first();

        if ($projet == null){
          //  dd($row, $row['projetservice'], $row['site']);
            return new Projet([
                'designation'=>$row['projetservice'],
                'site_id'=>$row['site'],
            ]);

        }else{
            $projet = Projet::where( 'designation' ,'=', $row['projetservice'])->first();
            $projet->site_id = $row['projetservice'];

            try{

                $projet->save();

            }catch (\Exception $e){
                echo'e';
                return redirect()->route('configuration.projet.liste');
            }

        }

    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function onError(\Throwable $e)
    {
        // Handle the exception how you'd like.
    }

}
