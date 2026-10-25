using System;
using System.Drawing;
using System.Drawing.Imaging;
using System.IO;

public class TestFaceInHair2 {
    public static void Main() {
        string assetsDir = @"C:\xampp1\htdocs\sdn\public\assets";
        Bitmap girl = new Bitmap(Path.Combine(assetsDir, "chibi_girl_full.png"));
        Bitmap s29 = new Bitmap(Path.Combine(assetsDir, @"scan_girl_fine\sec_2.90.png"));
        Bitmap s30 = new Bitmap(Path.Combine(assetsDir, @"scan_girl_fine\sec_3.00.png"));

        Bitmap girlHalf = ReplaceFace(girl, s29, 132, 64, 595, 117);
        Bitmap girlClosed = ReplaceFace(girl, s30, 132, 64, 598, 121);

        SaveFaceCrop(girl, Path.Combine(assetsDir, "face_crop_base.png"));
        SaveFaceCrop(girlHalf, Path.Combine(assetsDir, "face_crop_half.png"));
        SaveFaceCrop(girlClosed, Path.Combine(assetsDir, "face_crop_closed.png"));

        Console.WriteLine("Saved face_crop_base, half, closed 2!");
    }

    static Bitmap ReplaceFace(Bitmap baseGirl, Bitmap srcFrame, int capBaseX, int capBaseY, int capSrcX, int capSrcY) {
        Bitmap res = new Bitmap(baseGirl);
        int dx = capSrcX - capBaseX;
        int dy = capSrcY - capBaseY;

        // Face region: X 52..228, Y 195..295
        for (int gy = 195; gy <= 295; gy++) {
            for (int gx = 52; gx <= 228; gx++) {
                // Keep the dark hair lock on the sides if any:
                if (gx < 56 && gy < 235) continue;
                if (gx > 224 && gy < 240) continue;

                // Keep forehead hair tips if src is hair:
                int sx = gx + dx;
                int sy = gy + dy;
                if (sx < 0 || sx >= srcFrame.Width || sy < 0 || sy >= srcFrame.Height) continue;

                Color cSrc = srcFrame.GetPixel(sx, sy);
                // In forehead (gy < 205), don't replace skin with black hair fringe
                if (gy < 205 && cSrc.R < 65 && cSrc.G < 65 && cSrc.B < 65) continue;

                res.SetPixel(gx, gy, cSrc);
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
