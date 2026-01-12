# Konfigurasi Server untuk Upload File Material

## 1. PHP Configuration (php.ini)

Tambahkan atau update konfigurasi berikut di `php.ini`:

```ini
; Upload File Configuration
upload_max_filesize = 10M
post_max_size = 12M
max_execution_time = 300
max_input_time = 300
memory_limit = 256M

; File Upload Settings
file_uploads = On
max_file_uploads = 20
```

## 2. Apache Configuration

Jika menggunakan Apache, tambahkan konfigurasi berikut di `httpd.conf` atau virtual host:

```apache
# Increase upload size
LimitRequestBody 10485760  ; 10MB

# Enable multipart/form-data
LimitRequestFields 100
LimitRequestFieldSize 8190
```

## 3. Nginx Configuration

Jika menggunakan Nginx, tambahkan konfigurasi berikut di `nginx.conf` atau site config:

```nginx
http {
    # Increase upload size
    client_max_body_size 10M;
    client_body_timeout 60s;
    client_header_timeout 60s;
    
    # Increase buffer size for large uploads
    client_body_buffer_size 128k;
    client_header_buffer_size 1k;
    large_client_header_buffers 4 4k;
}

server {
    # Your existing server configuration
    
    # Handle multipart uploads
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;  # Adjust as needed
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        
        # Increase timeouts for large uploads
        fastcgi_read_timeout 300;
        fastcgi_send_timeout 300;
        fastcgi_connect_timeout 300;
    }
}
```

## 4. Laravel Configuration

### Filesystem Configuration (`config/filesystems.php`)

Pastikan konfigurasi public disk sudah benar:

```php
'disks' => [
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
    ],
],
```

### Environment Variables (`.env`)

```env
APP_URL=http://your-domain.com
ASSET_URL=http://your-domain.com
```

## 5. Permissions

Set proper permissions untuk storage:

```bash
# Linux/macOS
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/

# Create symbolic link (if not exists)
php artisan storage:link
```

## 6. Testing Upload

### cURL Command untuk Testing

```bash
# Test PDF Upload
curl -X POST \
  http://your-domain.com/api/materials \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Content-Type: multipart/form-data' \
  -F 'judul=Test Material' \
  -F 'tipe=pdf' \
  -F 'kategori=Test' \
  -F 'status=active' \
  -F 'file=@/path/to/test.pdf'

# Test Link Upload
curl -X POST \
  http://your-domain.com/api/materials \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Content-Type: multipart/form-data' \
  -F 'judul=Test Link Material' \
  -F 'tipe=link' \
  -F 'kategori=Test' \
  -F 'status=active' \
  -F 'link=https://example.com/document.pdf'
```

## 7. Debugging

### Check PHP Settings

```php
// Create test file: check_upload.php
<?php
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";
?>
```

### Laravel Logs

Check logs untuk debugging:
```bash
tail -f storage/logs/laravel.log
```

## 8. Common Issues & Solutions

### Error 413 Content Too Large
- Pastikan `client_max_body_size` di Nginx sudah 10M
- Pastikan `upload_max_filesize` dan `post_max_size` di PHP sudah cukup

### Error 422 Unprocessable Content
- Pastikan request menggunakan `multipart/form-data`
- Pastikan field name sesuai (`file` untuk PDF, `link` untuk URL)
- Pastikan file format PDF dan ukuran maksimal 10MB

### Permission Denied
- Pastikan folder storage memiliki permission yang benar
- Pastikan symbolic link sudah dibuat

### File Not Found
- Pastikan `php artisan storage:link` sudah dijalankan
- Pastikan file tersimpan di `storage/app/public/materials/`

## 9. Security Considerations

- Validasi file type (hanya PDF)
- Limit file size (maksimal 10MB)
- Use unique filename untuk prevent overwrite
- Store file di luar public directory jika perlu
- Implement virus scanning untuk production environment
