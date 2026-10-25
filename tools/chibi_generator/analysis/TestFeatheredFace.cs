using System;
using System.Drawing;
using System.Drawing.Imaging;
using System.IO;

public class TestFeatheredFace4 {
    public static void Main() {
        string assetsDir = @"C:\xampp1\htdocs\sdn\public\assets";
        Bitmap girl = new Bitmap(Path.Combine(assetsDir, "chibi_girl_full.png"));
        Bitmap s29 = new Bitmap(Path.Combine(assetsDir, @"scan_girl_fine\sec_2.90.png"));
        Bitmap s30 = new Bitmap(Path.Combine(assetsDir, @"scan_girl_fine\sec_3.00.png"));

        Bitmap girlHalf = BlendFaceWithFeather(girl, s29, 132, 64, 595, 117);
        Bitmap girlClosed = BlendFaceWithFeather(girl, s30, 132, 64, 598, 121);

        girlHalf.Save(Path.Combine(assetsDir, "girl_blink_half.png"), ImageFormat.Png);
        girlClosed.Save(Path.Combine(assetsDir, "girl_blink_closed.png"), ImageFormat.Png);

        SaveFaceCrop(girl, Path.Combine(assetsDir, "feather4_face_0_open.png"));
        SaveFaceCrop(girlHalf, Path.Combine(assetsDir, "feather4_face_1_half.png"));
        SaveFaceCrop(girlClosed, Path.Combine(assetsDir, "feather4_face_2_closed.png"));

        Console.WriteLine("Saved feather4_face_0..2.png!");
    }

    static Bitmap BlendFaceWithFeather(Bitmap baseGirl, Bitmap srcFrame, int capBaseX, int capBaseY, int capSrcX, int capSrcY) {
        Bitmap res = new Bitmap(baseGirl);
        int dx = capSrcX - capBaseX;
        int dy = capSrcY - capBaseY;

        int minX = 46, maxX = 234;
        int minY = 190, maxY = 294;
        int feather = 10;

        for (int gy = minY; gy <= maxY; gy++) {
            for (int gx = minX; gx <= maxX; gx++) {
                Color cBase = baseGirl.GetPixel(gx, gy);

                // Preserve dark hair fringe / bangs from baseGirl
                if (gy < 206 && (cBase.R < 130 && cBase.G < 130 && cBase.B < 130)) continue;

                // Preserve dark side hair locks
                if (gx < 54 && cBase.R < 60 && cBase.G < 60 && cBase.B < 60) continue;
                if (gx > 226 && cBase.R < 60 && cBase.G < 60 && cBase.B < 60) continue;

                int sx = gx + dx;
                int sy = gy + dy;
                if (sx < 0 || sx >= srcFrame.Width || sy < 0 || sy >= srcFrame.Height) continue;

                Color cSrc = srcFrame.GetPixel(sx, sy);
                // Don't paste dark hair from srcFrame over forehead skin
                if (gy < 205 && cSrc.R < 60 && cSrc.G < 60 && cSrc.B < 60) continue;

                int distLeft = gx - minX;
                int distRight = maxX - gx;
                int distTop = gy - minY;
                int distBottom = maxY - gy;

                int minDist = Math.Min(Math.Min(distLeft, distRight), Math.Min(distTop, distBottom));
                double alpha = 1.0;
                if (minDist < feather) {
                    alpha = (double)minDist / feather;
                }

                // If pixel in baseGirl is white sclera, force alpha=1.0 so no white sliver shows!
                if (cBase.R > 210 && cBase.G > 210 && cBase.B > 210) {
                    alpha = 1.0;
                }

                int r = (int)(cSrc.R * alpha + cBase.R * (1.0 - alpha));
                int g = (int)(cSrc.G * alpha + cBase.G * (1.0 - alpha));
                int b = (int)(cSrc.B * alpha + cBase.B * (1.0 - alpha));

                res.SetPixel(gx, gy, Color.FromArgb(cBase.A, r, g, b));
            }
        }
        return res;
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
