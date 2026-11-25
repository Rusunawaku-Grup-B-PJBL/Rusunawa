param(
    [string] $BaseUrl = 'http://127.0.0.1:8000'
)

$ErrorActionPreference = 'Stop'
$passed = 0
$failed = 0

Add-Type -AssemblyName System.Net.Http
$client = [System.Net.Http.HttpClient]::new()
$client.BaseAddress = [Uri]::new($BaseUrl.TrimEnd('/') + '/')

function Assert-Check {
    param(
        [string] $Name,
        [bool] $Condition,
        [string] $Details = ''
    )

    if ($Condition) {
        $script:passed++
        Write-Output "[LULUS] $Name"
    } else {
        $script:failed++
        $suffix = if ($Details) { " - $Details" } else { '' }
        Write-Output "[GAGAL] $Name$suffix"
    }
}

function Send-ApiRequest {
    param(
        [string] $Method,
        [string] $Path,
        [string] $Token,
        [hashtable] $JsonBody,
        [hashtable] $FormFields,
        [array] $Files
    )

    $request = [System.Net.Http.HttpRequestMessage]::new(
        [System.Net.Http.HttpMethod]::new($Method),
        $Path.TrimStart('/')
    )
    $request.Headers.Accept.Add(
        [System.Net.Http.Headers.MediaTypeWithQualityHeaderValue]::new('application/json')
    )

    if ($Token) {
        $request.Headers.Authorization = [System.Net.Http.Headers.AuthenticationHeaderValue]::new('Bearer', $Token)
    }

    if ($null -ne $FormFields) {
        $multipart = [System.Net.Http.MultipartFormDataContent]::new()
        foreach ($entry in $FormFields.GetEnumerator()) {
            $content = [System.Net.Http.StringContent]::new([string] $entry.Value)
            $multipart.Add($content, [string] $entry.Key)
        }

        foreach ($file in $Files) {
            $content = [System.Net.Http.ByteArrayContent]::new([byte[]] $file.Bytes)
            $content.Headers.ContentType = [System.Net.Http.Headers.MediaTypeHeaderValue]::Parse($file.ContentType)
            $multipart.Add($content, $file.FieldName, $file.FileName)
        }

        $request.Content = $multipart
    } elseif ($null -ne $JsonBody) {
        $json = ConvertTo-Json -InputObject $JsonBody -Depth 10 -Compress
        $request.Content = [System.Net.Http.StringContent]::new(
            $json,
            [System.Text.Encoding]::UTF8,
            'application/json'
        )
    }

    try {
        $response = $script:client.SendAsync($request).GetAwaiter().GetResult()
        $responseText = $response.Content.ReadAsStringAsync().GetAwaiter().GetResult()
        $body = if ($responseText) { $responseText | ConvertFrom-Json } else { $null }

        return [PSCustomObject]@{
            Status = [int] $response.StatusCode
            Body = $body
        }
    } catch {
        throw "Gagal menghubungi API di $($script:client.BaseAddress): $($_.Exception.Message)"
    } finally {
        $request.Dispose()
        if ($null -ne $response) {
            $response.Dispose()
        }
    }
}

function Invoke-TestCleanup {
    $cleanupPhp = '$user = \App\Models\User::query()->where("email", "tahap2-test@ubbg.ac.id")->first(); if ($user) { foreach ($user->bookings()->with("handovers")->get() as $booking) { if ($booking->payment_proof_path) \Illuminate\Support\Facades\Storage::disk("public")->delete($booking->payment_proof_path); foreach ($booking->handovers as $handover) { if ($handover->photo_path) \Illuminate\Support\Facades\Storage::disk("public")->delete($handover->photo_path); if ($handover->signature_path) \Illuminate\Support\Facades\Storage::disk("public")->delete($handover->signature_path); } } foreach ($user->damageReports()->get() as $report) { if ($report->photo_path) \Illuminate\Support\Facades\Storage::disk("public")->delete($report->photo_path); } $user->delete(); } \App\Models\Announcement::query()->where("title", "__UJI_INTERNAL_TAHAP2__")->delete();'
    $encodedCleanup = [Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes($cleanupPhp))
    $cleanupCode = "eval(base64_decode('$encodedCleanup')); echo '__CLEANUP_OK__';"
    $previousErrorPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    $cleanupOutput = & php artisan tinker "--execute=$cleanupCode" 2>&1
    $cleanupExitCode = $LASTEXITCODE
    $ErrorActionPreference = $previousErrorPreference

    if ($cleanupExitCode -ne 0 -or ($cleanupOutput -join "`n") -notmatch '__CLEANUP_OK__') {
        throw "Gagal membersihkan data uji Tahap 2.`n$($cleanupOutput -join "`n")"
    }
}

Push-Location (Join-Path $PSScriptRoot '..')
try {
    Invoke-TestCleanup

    $previousErrorPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    $routeOutput = & php artisan route:list --path=api 2>&1
    $routeExitCode = $LASTEXITCODE
    $ErrorActionPreference = $previousErrorPreference
    if ($routeExitCode -ne 0) {
        throw 'Gagal membaca daftar route Laravel.'
    }
    $routeText = $routeOutput -join "`n"
    $routeCount = if ($routeText -match 'Showing \[(\d+)\] routes') { [int] $matches[1] } else { 0 }
    Assert-Check 'Jumlah route API 17' ($routeCount -eq 17) "Ditemukan $routeCount route"

    $registration = Send-ApiRequest -Method 'POST' -Path 'api/register' -JsonBody @{
        name = 'Penghuni Uji Tahap 2'
        nim = 'TAHAP2TEST2026'
        email = 'tahap2-test@ubbg.ac.id'
        phone = '081234567890'
        password = 'password123'
        password_confirmation = 'password123'
    }
    Assert-Check 'Registrasi akun uji berhasil' ($registration.Status -eq 201 -and $registration.Body.data.token)
    if (-not $registration.Body.data.token) {
        throw 'Token akun uji tidak tersedia; pengujian endpoint terautentikasi tidak dapat dilanjutkan.'
    }

    $login = Send-ApiRequest -Method 'POST' -Path 'api/login' -JsonBody @{
        email = 'tahap2-test@ubbg.ac.id'
        password = 'password123'
    }
    Assert-Check 'Login akun uji berhasil' ($login.Status -eq 200 -and $login.Body.data.token)
    if (-not $login.Body.data.token) {
        throw 'Login akun uji gagal; pengujian endpoint terautentikasi tidak dapat dilanjutkan.'
    }
    $residentToken = [string] $login.Body.data.token

    $unauthorized = Send-ApiRequest -Method 'GET' -Path 'api/me'
    Assert-Check 'Token tidak ada menghasilkan 401 dengan format error' (
        $unauthorized.Status -eq 401 -and $null -ne $unauthorized.Body.errors
    )

    $announcements = Send-ApiRequest -Method 'GET' -Path 'api/announcements' -Token $residentToken
    $expectedTitles = @(
        'Jam malam mulai 22.00',
        'Jadwal piket Blok A: Senin',
        'Razia kamar Jumat 14.00'
    )
    $actualTitles = @($announcements.Body.data | ForEach-Object { $_.title })
    Assert-Check 'Daftar pengumuman mengikuti kontrak' (
        $announcements.Status -eq 200 -and
        ($expectedTitles | Where-Object { $_ -notin $actualTitles }).Count -eq 0
    )

    $rooms = Send-ApiRequest -Method 'GET' -Path 'api/rooms' -Token $residentToken
    $roomA101 = @($rooms.Body.data | Where-Object { $_.code -eq 'A-101' })[0]
    Assert-Check 'Daftar kamar berisi field ketersediaan' (
        $rooms.Status -eq 200 -and
        $null -ne $roomA101 -and
        $roomA101.name -eq 'Kamar A-101' -and
        $null -ne $roomA101.availability.label
    )

    $roomDetail = Send-ApiRequest -Method 'GET' -Path "api/rooms/$($roomA101.id)" -Token $residentToken
    Assert-Check 'Detail kamar memiliki fasilitas dan jadwal tujuh hari' (
        $roomDetail.Status -eq 200 -and
        $roomDetail.Body.data.schedule.Count -eq 7 -and
        $roomDetail.Body.data.facilities.Count -gt 0 -and
        $roomDetail.Body.data.schedule[0].date -eq (Get-Date -Format 'yyyy-MM-dd')
    )

    $today = (Get-Date).Date.ToString('yyyy-MM-dd')
    $pdfBytes = [System.Text.Encoding]::ASCII.GetBytes("%PDF-1.4`n1 0 obj`n<<>>`nendobj`n%%EOF")
    $pdfFile = @{
        FieldName = 'payment_proof'
        FileName = 'bukti.pdf'
        ContentType = 'application/pdf'
        Bytes = $pdfBytes
    }

    $fullRoom = @($rooms.Body.data | Where-Object { $_.code -eq 'B-201' })[0]
    $conflict = Send-ApiRequest -Method 'POST' -Path 'api/bookings' -Token $residentToken -FormFields @{
        room_id = [string] $fullRoom.id
        start_date = $today
        duration_months = '1'
        occupants = '1'
    } -Files @($pdfFile)
    Assert-Check 'Booking pada kamar penuh ditolak dengan 422' (
        $conflict.Status -eq 422 -and $null -ne $conflict.Body.errors.room_id
    )

    $availableRoom = @($rooms.Body.data | Where-Object { $_.code -eq 'C-301' })[0]
    $booking = Send-ApiRequest -Method 'POST' -Path 'api/bookings' -Token $residentToken -FormFields @{
        room_id = [string] $availableRoom.id
        start_date = $today
        duration_months = '1'
        occupants = '1'
    } -Files @($pdfFile)
    Assert-Check 'Booking tersedia berhasil dibuat dengan HTTP 201' (
        $booking.Status -eq 201 -and
        $booking.Body.data.payment_status -eq 'menunggu' -and
        $booking.Body.data.room.code -eq 'C-301'
    )
    $bookingId = $booking.Body.data.id

    $bookings = Send-ApiRequest -Method 'GET' -Path 'api/bookings/mine' -Token $residentToken
    Assert-Check 'Riwayat booking memuat booking penghuni' (
        $bookings.Status -eq 200 -and
        @($bookings.Body.data | Where-Object { $_.id -eq $bookingId }).Count -eq 1
    )

    $damage = Send-ApiRequest -Method 'POST' -Path 'api/damage-reports' -Token $residentToken -FormFields @{
        category = 'air'
        description = 'Kran kamar mandi bocor'
    }
    Assert-Check 'Laporan kerusakan berhasil dibuat' (
        $damage.Status -eq 201 -and
        $damage.Body.data.room_code -eq 'C-301' -and
        $damage.Body.data.logs.Count -eq 1
    )
    $damageId = $damage.Body.data.id

    $damageList = Send-ApiRequest -Method 'GET' -Path 'api/damage-reports/mine' -Token $residentToken
    Assert-Check 'Riwayat laporan memuat teknisi dan log' (
        $damageList.Status -eq 200 -and
        $null -ne (@($damageList.Body.data | Where-Object { $_.id -eq $damageId })[0].logs)
    )

    $pngBytes = [Convert]::FromBase64String(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lWQAAAAASUVORK5CYII='
    )
    $signatureFile = @{
        FieldName = 'signature'
        FileName = 'tanda-tangan.png'
        ContentType = 'image/png'
        Bytes = $pngBytes
    }
    $handoverFields = @{
        type = 'checkout'
        notes = ''
    }
    $itemNames = @(
        'Kunci kamar',
        'Kasur & bantal',
        'Lemari',
        'AC',
        'Lampu',
        'Kran & kamar mandi'
    )
    for ($index = 0; $index -lt 6; $index++) {
        $handoverFields["items[$index][item_name]"] = $itemNames[$index]
        $handoverFields["items[$index][condition]"] = 'baik'
    }

    $checkoutBeforeCheckin = Send-ApiRequest -Method 'POST' -Path 'api/handovers' -Token $residentToken `
        -FormFields $handoverFields -Files @($signatureFile)
    Assert-Check 'Check-out sebelum check-in ditolak dengan 422' (
        $checkoutBeforeCheckin.Status -eq 422 -and $null -ne $checkoutBeforeCheckin.Body.errors.type
    )

    $handoverFields.type = 'checkin'
    $checkin = Send-ApiRequest -Method 'POST' -Path 'api/handovers' -Token $residentToken `
        -FormFields $handoverFields -Files @($signatureFile)
    Assert-Check 'Check-in berhasil dengan enam item dan tanda tangan' (
        $checkin.Status -eq 201 -and
        $checkin.Body.data.type -eq 'checkin' -and
        $checkin.Body.data.items.Count -eq 6
    )

    $handoverFields.type = 'checkout'
    $checkout = Send-ApiRequest -Method 'POST' -Path 'api/handovers' -Token $residentToken `
        -FormFields $handoverFields -Files @($signatureFile)
    Assert-Check 'Check-out setelah check-in menutup booking' (
        $checkout.Status -eq 201 -and $checkout.Body.data.type -eq 'checkout'
    )

    $handovers = Send-ApiRequest -Method 'GET' -Path 'api/handovers/mine' -Token $residentToken
    Assert-Check 'Riwayat serah terima memuat check-in dan check-out' (
        $handovers.Status -eq 200 -and
        @($handovers.Body.data | Where-Object { $_.type -eq 'checkin' }).Count -gt 0 -and
        @($handovers.Body.data | Where-Object { $_.type -eq 'checkout' }).Count -gt 0
    )

    $forbidden = Send-ApiRequest -Method 'GET' -Path 'api/admin/bookings' -Token $residentToken
    Assert-Check 'Endpoint admin menolak penghuni dengan 403' (
        $forbidden.Status -eq 403 -and $null -ne $forbidden.Body.errors
    )

    $adminLogin = Send-ApiRequest -Method 'POST' -Path 'api/login' -JsonBody @{
        email = 'admin@ubbg.ac.id'
        password = 'password'
    }
    Assert-Check 'Login admin berhasil' ($adminLogin.Status -eq 200 -and $adminLogin.Body.data.token)
    if (-not $adminLogin.Body.data.token) {
        throw 'Token admin tidak tersedia; pengujian endpoint admin tidak dapat dilanjutkan.'
    }
    $adminToken = [string] $adminLogin.Body.data.token

    $adminBookings = Send-ApiRequest -Method 'GET' -Path 'api/admin/bookings' -Token $adminToken
    $adminReports = Send-ApiRequest -Method 'GET' -Path 'api/admin/damage-reports' -Token $adminToken
    Assert-Check 'Admin dapat membaca booking dan laporan' (
        $adminBookings.Status -eq 200 -and $adminReports.Status -eq 200
    )

    $paymentUpdate = Send-ApiRequest -Method 'PATCH' -Path "api/admin/bookings/$bookingId/payment" `
        -Token $adminToken -JsonBody @{ payment_status = 'terverifikasi' }
    Assert-Check 'Admin dapat memverifikasi pembayaran' (
        $paymentUpdate.Status -eq 200 -and $paymentUpdate.Body.data.payment_status -eq 'terverifikasi'
    )

    $reportUpdate = Send-ApiRequest -Method 'PATCH' -Path "api/admin/damage-reports/$damageId" `
        -Token $adminToken -JsonBody @{
            status = 'diproses'
            technician_name = 'Teknisi Uji'
            note = 'Pemeriksaan dijadwalkan.'
        }
    Assert-Check 'Admin dapat memperbarui laporan dan log teknisi' (
        $reportUpdate.Status -eq 200 -and
        $reportUpdate.Body.data.status -eq 'diproses' -and
        $reportUpdate.Body.data.technician_name -eq 'Teknisi Uji'
    )

    $announcement = Send-ApiRequest -Method 'POST' -Path 'api/admin/announcements' `
        -Token $adminToken -JsonBody @{
            title = '__UJI_INTERNAL_TAHAP2__'
            body = 'Pengumuman ini dibuat oleh skrip verifikasi.'
        }
    Assert-Check 'Admin dapat menerbitkan pengumuman' (
        $announcement.Status -eq 201 -and
        $announcement.Body.data.title -eq '__UJI_INTERNAL_TAHAP2__'
    )
} finally {
    try {
        Invoke-TestCleanup
    } finally {
        Pop-Location
        $client.Dispose()
    }
}

Write-Output "Hasil: $passed lulus, $failed gagal."
if ($failed -gt 0) {
    exit 1
}
