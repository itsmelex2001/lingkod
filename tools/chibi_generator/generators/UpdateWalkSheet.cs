using System;
using System.Drawing;
using System.Drawing.Imaging;
using System.IO;

public class UpdateWalkSheet {
    public static void Main() {
        string assetsDir = @"C:\xampp1\htdocs\sdn\public\assets";
        string sheetPath = Path.Combine(assetsDir, "girl_walk_spritesheet.png");
        string tempPath = Path.Combine(assetsDir, "girl_walk_spritesheet_temp.png");

        using (Bitmap orig = new Bitmap(sheetPath))
        using (Bitmap walkSheet = new Bitmap(orig))
        using (Bitmap halfGirl = new Bitmap(Path.Combine(assetsDir, "girl_blink_half.png")))
        using (Bitmap closedGirl = new Bitmap(Path.Combine(assetsDir, "girl_blink_closed.png"))) {
            int frameW = 360;
            int fx = 45, fy = 190, fw = 190, fh = 110;

            // Frame 7: Half-blink
            PasteFace(walkSheet, halfGirl, 7 * frameW, fx, fy, fw, fh);
            // Frame 8: Closed-blink
            PasteFace(walkSheet, closedGirl, 8 * frameW, fx, fy, fw, fh);
            // Frame 9: Half-blink
            PasteFace(walkSheet, halfGirl, 9 * frameW, fx, fy, fw, fh);

            walkSheet.Save(tempPath, ImageFormat.Png);
        }

        File.Copy(tempPath, sheetPath, true);
        File.Delete(tempPath);
        Console.WriteLine("Updated girl_walk_spritesheet.png with smooth multi-frame blinking!");
    }

    static void PasteFace(Bitmap sheet, Bitmap faceSource, int frameOffsetX, int fx, int fy, int fw, int fh) {
        for (int y = fy; y < fy + fh; y++) {
            for (int x = fx; x < fx + fw; x++) {
                Color c = faceSource.GetPixel(x, y);
                sheet.SetPixel(frameOffsetX + x, y, c);
            }
        }
    }
}
