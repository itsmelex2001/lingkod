using System;
using System.Drawing;
using System.IO;

public class MeasureColorDiff {
    public static void Main() {
        string assetsDir = @"C:\xampp1\htdocs\sdn\public\assets";
        Bitmap girl = new Bitmap(Path.Combine(assetsDir, "chibi_girl_full.png"));
        Bitmap s29 = new Bitmap(Path.Combine(assetsDir, @"scan_girl_fine\sec_2.90.png"));
        Bitmap s30 = new Bitmap(Path.Combine(assetsDir, @"scan_girl_fine\sec_3.00.png"));

        int dx29 = 595 - 132; // 463
        int dy29 = 117 - 64;  // 53
        int dx30 = 598 - 132; // 466
        int dy30 = 121 - 64;  // 57

        // Sample forehead skin at gx=140, gy=190 in girl:
        Color cg = girl.GetPixel(140, 190);
        Color c29 = s29.GetPixel(140 + dx29, 190 + dy29);
        Color c30 = s30.GetPixel(140 + dx30, 190 + dy30);

        Console.WriteLine(string.Format("Forehead: Girl=({0},{1},{2}), s29=({3},{4},{5}), s30=({6},{7},{8})",
            cg.R, cg.G, cg.B, c29.R, c29.G, c29.B, c30.R, c30.G, c30.B));

        // Sample nose skin at gx=142, gy=240 in girl:
        Color ng = girl.GetPixel(142, 240);
        Color n29 = s29.GetPixel(142 + dx29, 240 + dy29);
        Color n30 = s30.GetPixel(142 + dx30, 240 + dy30);

        Console.WriteLine(string.Format("Nose: Girl=({0},{1},{2}), s29=({3},{4},{5}), s30=({6},{7},{8})",
            ng.R, ng.G, ng.B, n29.R, n29.G, n29.B, n30.R, n30.G, n30.B));
    }
}
