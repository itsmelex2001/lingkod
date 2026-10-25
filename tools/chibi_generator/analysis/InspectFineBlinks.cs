using System;
using System.Drawing;
using System.IO;

public class InspectFineBlinks {
    public static void Main() {
        string dir = @"C:\xampp1\htdocs\sdn\public\assets\scan_girl_fine";
        string[] files = Directory.GetFiles(dir, "sec_*.png");
        Array.Sort(files);

        foreach (string file in files) {
            using (Bitmap bmp = new Bitmap(file)) {
                Point cap = FindRedCross(bmp, 400, 30, 400, 250);
                if (cap.IsEmpty) continue;

                // Eye area relative to cap:
                // Cap is at (cap.X, cap.Y).
                // Left eye (viewer left): X: cap.X - 80 to cap.X - 10, Y: cap.Y + 145 to cap.Y + 225
                // Count dark pixels in eye area (open pupil has lots of dark pixels, closed eye has fewer!)
                int darkEyePixels = 0;
                for (int y = cap.Y + 150; y < cap.Y + 225; y++) {
                    for (int x = cap.X - 80; x < cap.X - 10; x++) {
                        if (x < 0 || x >= bmp.Width || y < 0 || y >= bmp.Height) continue;
                        Color c = bmp.GetPixel(x, y);
                        if (c.R < 80 && c.G < 80 && c.B < 80) darkEyePixels++;
                    }
                }

                Console.WriteLine(string.Format("{0}: Cap=({1},{2}), DarkEyePixels={3}",
                    Path.GetFileName(file), cap.X, cap.Y, darkEyePixels));
            }
        }
    }

    static Point FindRedCross(Bitmap bmp, int startX, int startY, int w, int h) {
        int sumX = 0, sumY = 0, count = 0;
        for (int y = startY; y < Math.Min(bmp.Height, startY + h); y++) {
            for (int x = startX; x < Math.Min(bmp.Width, startX + w); x++) {
                Color c = bmp.GetPixel(x, y);
                if (c.R > 180 && c.G < 70 && c.B < 70) {
                    sumX += x;
                    sumY += y;
                    count++;
                }
            }
        }
        if (count == 0) return Point.Empty;
        return new Point(sumX / count, sumY / count);
    }
}
