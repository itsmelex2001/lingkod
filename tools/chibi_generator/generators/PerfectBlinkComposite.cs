using System;
using System.Drawing;
using System.Drawing.Imaging;
using System.IO;

public class PerfectBlinkComposite {
    public static void Main() {
        string assetsDir = @"C:\xampp1\htdocs\sdn\public\assets";
        Bitmap f0 = new Bitmap(Path.Combine(assetsDir, "raw_video_face_0.png"));
        Bitmap f1 = new Bitmap(Path.Combine(assetsDir, "raw_video_face_1.png"));
        Bitmap f2 = new Bitmap(Path.Combine(assetsDir, "raw_video_face_2.png"));
        Bitmap girl = new Bitmap(Path.Combine(assetsDir, "chibi_girl_full.png"));

        // Let's create diff map between f0 and f2
        Bitmap diffMap = new Bitmap(f0.Width, f0.Height);
        for (int y = 0; y < f0.Height; y++) {
            for (int x = 0; x < f0.Width; x++) {
                Color c0 = f0.GetPixel(x, y);
                Color c2 = f2.GetPixel(x, y);
                int d = Math.Abs(c0.R - c2.R) + Math.Abs(c0.G - c2.G) + Math.Abs(c0.B - c2.B);
                if (d > 35) { // noticeable change
                    diffMap.SetPixel(x, y, Color.White);
                } else {
                    diffMap.SetPixel(x, y, Color.Black);
                }
            }
        }
        diffMap.Save(Path.Combine(assetsDir, "face_diff_mask.png"), ImageFormat.Png);
        Console.WriteLine("Saved face_diff_mask.png");
    }
}
