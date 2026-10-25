Add-Type -AssemblyName PresentationCore, PresentationFramework, WindowsBase, System.Drawing

$mp = New-Object System.Windows.Media.MediaPlayer
$mp.ScrubbingEnabled = $true
$opened = $false
$mp.add_MediaOpened({ $script:opened = $true })
$mp.Open([Uri]"C:\xampp1\htdocs\sdn\public\assets\chatbot_chibi(boy).MP4")
for ($i=0; $i -lt 30 -and -not $opened; $i++) { Start-Sleep -Milliseconds 100 }

for ($s = 1.0; $s -le 2.2; $s += 0.2) {
    $mp.Position = [TimeSpan]::FromSeconds($s)
    $mp.Pause()
    Start-Sleep -Milliseconds 250
    $dv = New-Object System.Windows.Media.DrawingVisual
    $dc = $dv.RenderOpen()
    $dc.DrawVideo($mp, (New-Object System.Windows.Rect(0, 0, 1280, 720)))
    $dc.Close()
    $rtb = New-Object System.Windows.Media.Imaging.RenderTargetBitmap(1280, 720, 96, 96, [System.Windows.Media.PixelFormats]::Pbgra32)
    $rtb.Render($dv)
    $enc = New-Object System.Windows.Media.Imaging.PngBitmapEncoder
    $enc.Frames.Add([System.Windows.Media.Imaging.BitmapFrame]::Create($rtb))
    $ms = New-Object System.IO.MemoryStream
    $enc.Save($ms)
    $ms.Position = 0
    $bmp = [System.Drawing.Bitmap]::FromStream($ms)
    $bg = $bmp.GetPixel(10, 10)
    
    # Find top-most hair pixel (x: 400..800)
    $topY = 999
    for ($y = 0; $y -lt 200; $y++) {
        for ($x = 400; $x -lt 800; $x++) {
            $c = $bmp.GetPixel($x, $y)
            $dist = [Math]::Sqrt([Math]::Pow($c.R - $bg.R, 2) + [Math]::Pow($c.G - $bg.G, 2) + [Math]::Pow($c.B - $bg.B, 2))
            if ($dist -gt 30) {
                if ($y -lt $topY) { $topY = $y }
            }
        }
        if ($topY -ne 999) { break }
    }
    Write-Host "At ${s}s, top-most pixel is at Y = $topY"
    $bmp.Dispose()
    $ms.Dispose()
}
$mp.Close()
