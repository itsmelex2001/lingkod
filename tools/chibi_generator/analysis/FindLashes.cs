using System;
using System.Drawing;
using System.IO;

public class FindLashes {
    public static void Main() {
        string assetsDir = @"C:\xampp1\htdocs\sdn\public\assets";
        Bitmap f2 = new Bitmap(Path.Combine(assetsDir, "raw_video_face_2.png"));
        // Print all dark pixels (lashes) in f2 for left eye (x: 40..100) and right eye (x: 120..180)
        int lMinY=999, lMaxY=0, rMinY=999, rMaxY=0;
        for (int y = 0; y < f2.Height; y++) {
            for (int x = 0; x < f2.Width; x++) {
                Color c = f2.GetPixel(x, y);
                if (c.R < 80 && c.G < 80 && c.B < 80) { // dark lash/eyebrow
                    if (x >= 40 && x <= 100) {
                        lMinY = Math.Min(lMinY, y);
                        lMaxY = Math.Max(lMaxY, y);
                    }
                    if (x >= 120 && x <= 180) {
                        rMinY = Math.Min(rMinY, y);
                        rMaxY = Math.Max(rMaxY, y);
                    }
                }
            }
        }
        Console.WriteLine(string.Format("In f2: Left dark Y is {0}..{1}, Right dark Y is {2}..{3}", lMinY, lMaxY, rMinY, rMaxY));

        // Now print row by row in f2 for left eye column x=70
        Console.WriteLine("f2 column x=70 (left eye center):");
        for (int y = 15; y < 90; y++) {
            Color c = f2.GetPixel(70, y);
            if (c.R < 80 && c.G < 80 && c.B < 80) {
                Console.WriteLine(string.Format("  y={0}: DARK LASH/BROW ({1},{2},{3})", y, c.R, c.G, c.B));
            }
        }

        Bitmap f0 = new Bitmap(Path.Combine(assetsDir, "raw_video_face_0.png"));
        Console.WriteLine("f0 column x=70 (left eye center):");
        for (int y = 15; y < 90; y++) {
            Color c = f0.GetPixel(70, y);
            if (c.R < 80 && c.G < 80 && c.B < 80) {
                Console.WriteLine(string.Format("  y={0}: DARK LASH/BROW/PUPIL ({1},{2},{3})", y, c.R, c.G, c.B));
            }
        }
    }
}
