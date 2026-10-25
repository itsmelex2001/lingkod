using System;
using System.Drawing;
using System.Drawing.Imaging;
using System.IO;

public class CropRawFaces {
    public static void Main() {
        string assetsDir = @"C:\xampp1\htdocs\sdn\public\assets";
        string[] files = new string[] {
            @"scan_girl_fine\sec_2.80.png",
            @"scan_girl_fine\sec_2.90.png",
            @"scan_girl_fine\sec_3.00.png",
            @"scan_girl_fine\sec_3.10.png"
        };

        for (int i = 0; i < files.Length; i++) {
            using (Bitmap bmp = new Bitmap(Path.Combine(assetsDir, files[i]))) {
                Point cap = FindRedCross(bmp, 400, 30, 400, 250);
                Console.WriteLine(string.Format("{0}: Cap at ({1},{2})", files[i], cap.X, cap.Y));
                
                // Crop face relative to cap: cap is roughly top center of head (around forehead/hair)
                // In chibi_girl_full, cap is at (132, 64), and face crop was (40..240, 180..310) -> (cap.X - 92, cap.Y + 116)
                int fx = cap.X - 100;
                int fy = cap.Y + 100;
                int fw = 200;
                int fh = 140;

                Bitmap crop = new Bitmap(fw, fh, PixelFormat.Format32bppArgb);
                using (Graphics g = Graphics.FromImage(crop)) {
                    g.DrawImage(bmp, new Rectangle(0, 0, fw, fh), new Rectangle(fx, fy, fw, fh), GraphicsUnit.Pixel);
                }
                crop.Save(Path.Combine(assetsDir, string.Format("raw_video_face_{0}.png", i)), ImageFormat.Png);
                crop.Dispose();
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
