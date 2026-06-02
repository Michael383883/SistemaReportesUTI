<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    public function up(): void
    {
        
        DB::statement('
            CREATE TABLE RESOLUCIONES_PDF (
                ID_RESOLUCION  INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
                NRO_RESOLUCION NVARCHAR(50)      NOT NULL,
                DESCRIPCION    NVARCHAR(200)     NULL,
                ANIO           INT               NOT NULL,
                PERIODO        NVARCHAR(2)       NOT NULL,
                ARCHIVO_PDF    VARBINARY(MAX)    NOT NULL,
                NOMBRE_ARCHIVO NVARCHAR(200)     NOT NULL,
                TAMANIO_KB     INT               NULL,
                FECHA_SUBIDA   DATETIME2         NOT NULL DEFAULT GETDATE(),
                SUBIDO_POR     NVARCHAR(100)     NULL
            )
        ');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS RESOLUCIONES_PDF');
    }
};