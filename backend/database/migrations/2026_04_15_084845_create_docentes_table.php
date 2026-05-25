<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Replica exacta de SQL Server 2008 usando tipos nativos raw
     */
    public function up(): void
    {
        // Eliminar si existe
        Schema::dropIfExists('DOCENTES');

        // Crear tabla con tipos EXACTOS de SQL Server 2008
        DB::statement('
            CREATE TABLE [dbo].[DOCENTES] (
                [CODIGO]             NUMERIC(9, 0)  NOT NULL,  -- ← 9 igual que el origen
                [CI]                 VARCHAR(13)    NOT NULL,
                [NOMBRES]            VARCHAR(40)    NULL,
                [APELLIDOS]          VARCHAR(30)    NULL,
                [FECHA_NAC]          DATETIME       NULL,
                [SEXO]               VARCHAR(1)     NOT NULL,
                [TITULO]             VARCHAR(4)     NULL,
                [FECHA_NOMBRAMIENTO] DATETIME       NULL,

                CONSTRAINT [PK_DOCENTES] PRIMARY KEY CLUSTERED ([CODIGO] ASC)
            )
        ');

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('DOCENTES');
    }
};