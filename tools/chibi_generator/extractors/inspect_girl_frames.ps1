Add-Type -AssemblyName System.Drawing

function Inspect-Frames {
    param([string]$dir)
    $files = Get-ChildItem $dir -Filter 'frame_*.png' | Sort-Object Name
    foreach ($f in $files) {
        $bmp = [System.Drawing.Bitmap]::FromFile($f.FullName)
        $minX = 9999; $maxX = 0; $minY = 9999; $maxY = 0
        for ($y = 0; $y -lt $bmp.Height; $y += 6) {
            for ($x = 0; $x -lt $bmp.Width; $x += 6) {
                $c = $bmp.GetPixel($x, $y)
                if ($c.R -lt 240 -or $c.G -lt 240 -or $c.B -lt 240) {
                    if ($x -lt $minX) { $minX = $x }
                    if ($x -gt $maxX) { $maxX = $x }
                    if ($y -lt $minY) { $minY = $y }
                    if ($y -gt $maxY) { $maxY = $y }
                }
            }
        }
        $bmp.Dispose()
        Write-Host ("{0} -> X: {1}..{2} (w:{3}), Y: {4}..{5} (h:{6})" -f $f.Name, $minX, $maxX, ($maxX - $minX), $minY, $maxY, ($maxY - $minY))
    }
}

Inspect-Frames -dir "C:\xampp1\htdocs\sdn\public\assets\chibi_bg_girl"
