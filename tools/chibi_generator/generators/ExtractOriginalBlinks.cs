using System;
using System.Drawing;
using System.Drawing.Imaging;
using System.IO;

public class ExtractOriginalBlinks {
    public static void Main() {
        string assetsDir = @"C:\xampp1\htdocs\sdn\public\assets";
        string scanDir = Path.Combine(assetsDir, "scan_girl_fine");
        string[] samples = new string[] {
            "sec_1.70.png", "sec_1.80.png", "sec_1.90.png", "sec_2.00.png",
            "sec_2.80.png", "sec_2.90.png", "sec_3.00.png"
        };

        foreach (string name in samples) {
            string p = Path.Combine(scanDir, name);
            if (!File.Exists(p)) continue;
            using (Bitmap bmp = new Bitmap(p)) {
                Point cap = FindRedCross(bmp, 400, 30, 400, 250);
                if (cap.IsEmpty) continue;

                // Crop face relative to cap:
                // Cap is at (cap.X, cap.Y).
                // Face is: X: cap.X - 100 to cap.X + 80, Y: cap.Y + 110 to cap.Y + 220
                Bitmap crop = new Bitmap(180, 110, PixelFormat.Format32bppArgb);
                using (Graphics g = Graphics.FromImage(crop)) {
                    g.DrawImage(bmp, new Rectangle(0, 0, 180, 110),
                        new Rectangle(cap.X - 100, cap.Y + 110, 180, 110), GraphicsUnit.Pixel);
                }
                crop.Save(Path.Combine(assetsDir, "orig_blink_" + name), ImageFormat.Png);
            }
        }
        Console.WriteLine("Saved original blink crops!");
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
