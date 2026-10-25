Add-Type -AssemblyName System.Drawing

$srcPath = "C:\Users\akosi\.gemini\antigravity-ide\brain\0e637bcd-c868-4ba0-a6d6-d1eb5441a229\.user_uploaded\media_1790012789850.png"
$src = [System.Drawing.Bitmap]::FromFile($srcPath)

# 1. Inspect Doctor Icon region around (780..870, 350..430)
# Find non-white/non-background pixels
Write-Host "Image size: $($src.Width) x $($src.Height)"

# 2. Crop Linkod: roughly x: 265..520, y: 80..220
$rectLinkod = New-Object System.Drawing.Rectangle(265, 80, 255, 140)
$cropLinkod = New-Object System.Drawing.Bitmap($rectLinkod.Width, $rectLinkod.Height)
$g = [System.Drawing.Graphics]::FromImage($cropLinkod)
$g.DrawImage($src, 0, 0, $rectLinkod, [System.Drawing.GraphicsUnit]::Pixel)
$g.Dispose()

# Make pure white transparent in Linkod
for ($x = 0; $x -lt $cropLinkod.Width; $x++) {
    for ($y = 0; $y -lt $cropLinkod.Height; $y++) {
        $c = $cropLinkod.GetPixel($x, $y)
        # If near white (R>245, G>245, B>245)
        if ($c.R -gt 240 -and $c.G -gt 240 -and $c.B -gt 240) {
            $cropLinkod.SetPixel($x, $y, [System.Drawing.Color]::FromArgb(0, 255, 255, 255))
        }
    }
}
$cropLinkod.Save("c:\xampp\htdocs\sdn\public\assets\linkod_brush.png", [System.Drawing.Imaging.ImageFormat]::Png)
$cropLinkod.Dispose()

# 3. Crop Doctor: x: 785..875, y: 350..435
$rectDoc = New-Object System.Drawing.Rectangle(785, 350, 88, 85)
$cropDoc = New-Object System.Drawing.Bitmap($rectDoc.Width, $rectDoc.Height)
$g2 = [System.Drawing.Graphics]::FromImage($cropDoc)
$g2.DrawImage($src, 0, 0, $rectDoc, [System.Drawing.GraphicsUnit]::Pixel)
$g2.Dispose()

# For doctor, outer background is #f1f5f2 or #ffffff
for ($x = 0; $x -lt $cropDoc.Width; $x++) {
    for ($y = 0; $y -lt $cropDoc.Height; $y++) {
        $c = $cropDoc.GetPixel($x, $y)
        # If it's the light greyish background around the circle
        if (($c.R -gt 230 -and $c.G -gt 235 -and $c.B -gt 230) -or ($c.R -gt 245 -and $c.G -gt 245 -and $c.B -gt 245)) {
            $cropDoc.SetPixel($x, $y, [System.Drawing.Color]::FromArgb(0, 0, 0, 0))
        }
    }
}
$cropDoc.Save("c:\xampp\htdocs\sdn\public\assets\doctor_bot.png", [System.Drawing.Imaging.ImageFormat]::Png)
$cropDoc.Dispose()

$src.Dispose()
Write-Host "Cropped successfully!"
