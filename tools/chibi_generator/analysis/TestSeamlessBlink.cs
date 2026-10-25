using System;
using System.Drawing;
using System.Drawing.Imaging;
using System.IO;

public class TestSeamlessBlink6 {
    public static void Main() {
        string assetsDir = @"C:\xampp1\htdocs\sdn\public\assets";
        Bitmap girl = new Bitmap(Path.Combine(assetsDir, "chibi_girl_full.png"));
        Bitmap f1 = new Bitmap(Path.Combine(assetsDir, "raw_video_face_1.png"));
        Bitmap f2 = new Bitmap(Path.Combine(assetsDir, "raw_video_face_2.png"));

        Bitmap girlClosed = new Bitmap(girl);

        // Replace only eye pixels inside eye sockets:
        // Left eye box: X 52..130, Y 208..282
        // Right eye box: X 158..232, Y 208..278
        ReplaceEyePixelsOnly(girlClosed, 52, 130, 208, 282, 90, 244);
        ReplaceEyePixelsOnly(girlClosed, 158, 232, 208, 278, 195, 242);

        // Overlay authentic closed lashes from f2 (fy: 65..105):
        // In chibi_girl_full, cropOrigin is (32, 164).
        for (int fy = 65; fy <= 105; fy++) {
            for (int fx = 35; fx <= 190; fx++) {
                Color cf2 = f2.GetPixel(fx, fy);
                bool isLash = (cf2.R < 90 && cf2.G < 90 && cf2.B < 90);
                bool isCrease = (cf2.R < 170 && cf2.G < 135 && cf2.B < 125 && (cf2.R - cf2.G) > 20);

                if (isLash || isCrease) {
                    int gx = 32 + fx;
                    int gy = 164 + fy;
                    if (gx < girlClosed.Width && gy < gy && gy < girlClosed.Height) { }
                    if (gx >= 52 && gx <= 226 && gy >= 208 && gy < girlClosed.Height) {
                        girlClosed.SetPixel(gx, gy, cf2);
                    }
                }
            }
        }

        SaveFaceCrop(girlClosed, Path.Combine(assetsDir, "test_seamless_closed6.png"));
        Console.WriteLine("Saved test_seamless_closed6.png");
    }

    static void ReplaceEyePixelsOnly(Bitmap target, int minX, int maxX, int minY, int maxY, int eyeCx, int eyeCy) {
        for (int y = minY; y <= maxY; y++) {
            // Find skin color on left and right borders of this line, or above/below
            double t = (double)(y - minY) / (maxY - minY);
            // Vertical skin gradient from base forehead skin to blush cheek skin:
            int sr = (int)(242 * (1 - t) + 242 * t);
            int sg = (int)(203 * (1 - t) + 195 * t);
            int sb = (int)(176 * (1 - t) + 172 * t);
            Color skinAtY = Color.FromArgb(255, sr, sg, sb);

            for (int x = minX; x <= maxX; x++) {
                // Don't touch hair on far left
                if (x < 55 && y < 225) continue;

                Color c = target.GetPixel(x, y);
                // Is this pixel eye (sclera white, iris/pupil, open eyelash)?
                // Sclera: white / greyish white (R > 210 && G > 210 && B > 210)
                // Iris/pupil/open lash: (R < 100 && G < 100) or (R < 140 && B > R - 10)
                bool isSclera = (c.R > 210 && c.G > 210 && c.B > 210);
                bool isDarkEye = (c.R < 110 && c.G < 110 && c.B < 115);
                bool isIris = (c.R < 150 && c.G < 150 && c.B > 120 && Math.Abs(c.R - c.G) < 20);

                if (isSclera || isDarkEye || isIris) {
                    target.SetPixel(x, y, skinAtY);
                }
            }
        }
    }

    static void SaveFaceCrop(Bitmap src, string outPath) {
        Bitmap face = new Bitmap(200, 140, PixelFormat.Format32bppArgb);
        using (Graphics g = Graphics.FromImage(face)) {
            g.DrawImage(src, new Rectangle(0, 0, 200, 140), new Rectangle(32, 164, 200, 140), GraphicsUnit.Pixel);
        }
        face.Save(outPath, ImageFormat.Png);
        face.Dispose();
    }
}
