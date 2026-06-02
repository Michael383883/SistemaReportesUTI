<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    public function up(): void
    {
        // DDL directo para control total en SQL Server
        DB::statement('
            CREATE TABLE RESOLUCION_DETALLE (
                ID_DETALLE     INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
                ID_RESOLUCION  INT               NOT NULL,
                COD_DOCENTE    NUMERIC(10,0)     NOT NULL,
                COD_PLAN       NVARCHAR(10)      NOT NULL,
                COD_MATERIA    NVARCHAR(10)      NOT NULL,
                GRUPO          NVARCHAR(5)       NULL,
                TIPO           NVARCHAR(2)       NULL,
                OBSERVACION    NVARCHAR(200)     NULL,

                CONSTRAINT FK_DETALLE_RESOLUCION
                    FOREIGN KEY (ID_RESOLUCION)
                    REFERENCES RESOLUCIONES_PDF(ID_RESOLUCION)
                    ON DELETE CASCADE
            )
        ');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS RESOLUCION_DETALLE');
    }
};