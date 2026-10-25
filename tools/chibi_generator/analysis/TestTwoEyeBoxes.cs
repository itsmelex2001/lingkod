using System;
using System.Drawing;
using System.Drawing.Imaging;
using System.IO;

public class TestTwoEyeBoxes2 {
    public static void Main() {
        string assetsDir = @"C:\xampp1\htdocs\sdn\public\assets";
        Bitmap girl = new Bitmap(Path.Combine(assetsDir, "chibi_girl_full.png"));
        Bitmap s29 = new Bitmap(Path.Combine(assetsDir, @"scan_girl_fine\sec_2.90.png"));
        Bitmap s30 = new Bitmap(Path.Combine(assetsDir, @"scan_girl_fine\sec_3.00.png"));

        Bitmap girlHalf = BlendTwoEyes(girl, s29, 132, 64, 595, 117);
        Bitmap girlClosed = BlendTwoEyes(girl, s30, 132, 64, 598, 121);

        girlHalf.Save(Path.Combine(assetsDir, "girl_blink_half.png"), ImageFormat.Png);
        girlClosed.Save(Path.Combine(assetsDir, "girl_blink_closed.png"), ImageFormat.Png);

        SaveFaceCrop(girl, Path.Combine(assetsDir, "box2_0_open.png"));
        SaveFaceCrop(girlHalf, Path.Combine(assetsDir, "box2_1_half.png"));
        SaveFaceCrop(girlClosed, Path.Combine(assetsDir, "box2_2_closed.png"));

        Console.WriteLine("Saved box2_0..2.png!");
    }

    static Bitmap BlendTwoEyes(Bitmap baseGirl, Bitmap srcFrame, int capBaseX, int capBaseY, int capSrcX, int capSrcY) {
        Bitmap res = new Bitmap(baseGirl);
        int dx = capSrcX - capBaseX;
        int dy = capSrcY - capBaseY;

        // Left eye box: X 46..134, Y 212..286
        BlendBox(res, baseGirl, srcFrame, 46, 134, 212, 286, dx, dy, 7, false);

        // Right eye box: X 146..232, Y 198..284 (starts at 198 to cleanly cover right eyebrow above it)
        BlendBox(res, baseGirl, srcFrame, 146, 232, 198, 284, dx, dy, 7, true);

        return res;
    }

    static void BlendBox(Bitmap res, Bitmap baseGirl, Bitmap srcFrame, int minX, int maxX, int minY, int maxY, int dx, int dy, int feather, bool isRightEye) {
        for (int gy = minY; gy <= maxY; gy++) {
            for (int gx = minX; gx <= maxX; gx++) {
                Color cBase = baseGirl.GetPixel(gx, gy);
                // Don't overwrite side hair fringe
                if (gx < 52 && cBase.R < 60 && cBase.G < 60 && cBase.B < 60) continue;
                if (gx > 226 && cBase.R < 60 && cBase.G < 60 && cBase.B < 60) continue;

                int sx = gx + dx;
                int sy = gy + dy;
                if (sx < 0 || sx >= srcFrame.Width || sy < 0 || sy >= srcFrame.Height) continue;

                Color cSrc = srcFrame.GetPixel(sx, sy);

                int distLeft = gx - minX;
                int distRight = maxX - gx;
                int distTop = gy - minY;
                int distBottom = maxY - gy;

                int minDist = Math.Min(Math.Min(distLeft, distRight), Math.Min(distTop, distBottom));
                double alpha = 1.0;
                if (minDist < feather) {
                    alpha = (double)minDist / feather;
                }

                int r = (int)(cSrc.R * alpha + cBase.R * (1.0 - alpha));
                int g = (int)(cSrc.G * alpha + cBase.R * (1.0 - alpha));
                int b = (int)(cSrc.B * alpha + cBase.B * (1.0 - alpha));

                res.SetPixel(gx, gy, Color.FromArgb(cBase.A, r, g, b));
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
