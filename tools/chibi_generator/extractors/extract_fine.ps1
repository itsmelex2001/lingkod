Add-Type -AssemblyName PresentationCore, PresentationFramework, WindowsBase
function Extract-Fine {
    param([string]$videoPath, [string]$outDir, [double]$startSec, [int]$numFrames, [double]$fps)
    $mp = New-Object System.Windows.Media.MediaPlayer
    $mp.ScrubbingEnabled = $true
    $opened = $false
    $mp.add_MediaOpened({ $script:opened = $true })
    $mp.Open([Uri]$videoPath)
    for ($i = 0; $i -lt 30 -and -not $opened; $i++) { Start-Sleep -Milliseconds 100 }
    if (-not (Test-Path $outDir)) { New-Item -ItemType Directory -Path $outDir -Force | Out-Null }
    for ($i = 0; $i -lt $numFrames; $i++) {
        $sec = $startSec + ($i / $fps)
        $mp.Position = [TimeSpan]::FromSeconds($sec)
        $mp.Pause()
        Start-Sleep -Milliseconds 180
        $dv = New-Object System.Windows.Media.DrawingVisual
        $dc = $dv.RenderOpen()
        $dc.DrawVideo($mp, (New-Object System.Windows.Rect(0, 0, 1280, 720)))
        $dc.Close()
        $rtb = New-Object System.Windows.Media.Imaging.RenderTargetBitmap(1280, 720, 96, 96, [System.Windows.Media.PixelFormats]::Pbgra32)
        $rtb.Render($dv)
        $enc = New-Object System.Windows.Media.Imaging.PngBitmapEncoder
        $enc.Frames.Add([System.Windows.Media.Imaging.BitmapFrame]::Create($rtb))
        $out = Join-Path $outDir ("fine_{0:D2}.png" -f $i)
        $fs = [System.IO.File]::Create($out)
        $enc.Save($fs)
        $fs.Close()
    }
    $mp.Close()
    Write-Host "Done"
}
Extract-Fine -videoPath "C:\xampp1\htdocs\sdn\public\assets\chibi boy and girl.mp4" -outDir "C:\xampp1\htdocs\sdn\public\assets\chibi_girl_fine" -startSec 2.0 -numFrames 12 -fps 10
