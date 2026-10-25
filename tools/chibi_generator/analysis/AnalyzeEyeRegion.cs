using System;
using System.Drawing;
using System.IO;

public class AnalyzeEyeRegion {
    public static void Main() {
        string assetsDir = @"C:\xampp1\htdocs\sdn\public\assets";
        Bitmap f0 = new Bitmap(Path.Combine(assetsDir, "raw_video_face_0.png"));
        Bitmap f2 = new Bitmap(Path.Combine(assetsDir, "raw_video_face_2.png"));

        // In f0 (200x140), let's inspect left eye (x: 20..110) and right eye (x: 110..195)
        // Find min/max X and Y where pixel is iris or sclera (sclera: white, iris: dark)
        int lMinX=200, lMaxX=0, lMinY=140, lMaxY=0;
        int rMinX=200, rMaxX=0, rMinY=140, rMaxY=0;

        for (int y = 20; y < 120; y++) {
            for (int x = 20; x < 195; x++) {
                Color c = f0.GetPixel(x, y);
                // Iris/lash: dark (R < 100) or sclera: white (R > 230 && G > 230 && B > 230)
                bool isEye = (c.R < 100 && c.G < 100 && c.B < 100) || (c.R > 230 && c.G > 230 && c.B > 230);
                if (isEye) {
                    if (x < 110) {
                        lMinX = Math.Min(lMinX, x); lMaxX = Math.Max(lMaxX, x);
                        lMinY = Math.Min(lMinY, y); lMaxY = Math.Max(lMaxY, y);
                    } else {
                        rMinX = Math.Min(rMinX, x); rMaxX = Math.Max(rMaxX, x);
                        rMinY = Math.Min(rMinY, y); rMaxY = Math.Max(rMaxY, y);
                    }
                }
            }
        }
        Console.WriteLine(string.Format("Left eye in f0: X {0}..{1} (center {2}, w={3}), Y {4}..{5} (center {6}, h={7})",
            lMinX, lMaxX, (lMinX+lMaxX)/2, lMaxX-lMinX, lMinY, lMaxY, (lMinY+lMaxY)/2, lMaxY-lMinY));
        Console.WriteLine(string.Format("Right eye in f0: X {0}..{1} (center {2}, w={3}), Y {4}..{5} (center {6}, h={7})",
            rMinX, rMaxX, (rMinX+rMaxX)/2, rMaxX-rMinX, rMinY, rMaxY, (rMinY+rMaxY)/2, rMaxY-rMinY));
    }
}
