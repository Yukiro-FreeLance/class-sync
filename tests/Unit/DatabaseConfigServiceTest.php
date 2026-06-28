<?php

namespace Tests\Unit;

use App\DTOs\Setup\DatabaseConfigDTO;
use App\Services\Setup\DatabaseConfigService;
use App\Services\Setup\EnvWriterService;
use Tests\TestCase;

class DatabaseConfigServiceTest extends TestCase
{
    public function test_it_rejects_invalid_database_names(): void
    {
        $service = new DatabaseConfigService(new EnvWriterService);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database name may only contain letters');

        $service->ensureDatabaseExists(new DatabaseConfigDTO(
            driver: 'mysql',
            host: '127.0.0.1',
            port: 3306,
            database: 'bad-name',
            username: 'root',
            password: '',
        ));
    }
}
