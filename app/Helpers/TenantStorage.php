<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Tenancy\Facades\Tenancy; // o usa tu forma de obtener tenant actual

class TenantStorage
{
    public static function path($relativePath)
    {
        $tenant = tenant(); //helper para obtener el tenant actual
      //dd($tenant->fqdn); helper para obtener el hostname demo.pro7.test
         if (!$tenant) {
        throw new \Exception("Tenant no resuelto configurar el middleware de tenancy");
    }

      $folder = $tenant->website->uuid;
      $tenantPath = "tenancy/tenants/{$folder}/" . ltrim($relativePath, '/');
      return storage_path("app/" . $tenantPath);
    }

    public static function get($path)
    {
        return file_get_contents(self::path($path));
    }

    public static function put($path, $content)
    {
        return file_put_contents(self::path($path), $content);
    }

    public static function exists($path)
    {
        return file_exists(self::path($path));
    }
}
