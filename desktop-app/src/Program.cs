using System;
using System.Collections.Generic;
using System.Drawing;
using System.Drawing.Drawing2D;
using System.IO;
using System.Net;
using System.Text;
using System.Threading;
using System.Web.Script.Serialization;
using System.Windows.Forms;
using System.Windows.Media;

namespace RadioAgroDesktop
{
    public class AppConfig
    {
        public string station_name { get; set; }
        public string stream_url { get; set; }
        public string api_url { get; set; }
        public bool auto_play { get; set; }
        public bool minimize_to_tray { get; set; }
        public int volume { get; set; }

        public AppConfig()
        {
            station_name = "Radio Agro";
            stream_url = "http://localhost:8000/live";
            api_url = "http://localhost:8080";
            auto_play = false;
            minimize_to_tray = true;
            volume = 85;
        }
    }

    public static class Program
    {
        [STAThread]
        public static void Main()
        {
            Application.EnableVisualStyles();
            Application.SetCompatibleTextRenderingDefault(false);
            Application.Run(new MainWindow());
        }
    }

    public class MainWindow : Form
    {
        private AppConfig config;
        private string configPath;
        private MediaPlayer mediaPlayer;
        private bool isPlaying = false;

        // UI Controls
        private System.Windows.Forms.Timer telemetryTimer;
        private System.Windows.Forms.Timer visualizerTimer;
        private NotifyIcon trayIcon;
        private ContextMenuStrip trayMenu;

        private TabControl mainTabControl;
        private TabPage tabPlayer;
        private TabPage tabBrowser;
        private TabPage tabRequest;
        private TabPage tabSettings;

        // Player Controls
        private Button btnPlay;
        private TrackBar volumeBar;
        private Label lblVolume;
        private Label lblStatusBadge;
        private Label lblTrackTitle;
        private Label lblTrackArtist;
        private Label lblListeners;
        private Label lblBitrate;
        private Label lblMode;
        private Label lblNextTrack;
        private Panel visualizerPanel;

        // Browser & Request Controls
        private WebBrowser webBrowser;
        private TextBox txtReqName;
        private TextBox txtReqSong;
        private TextBox txtReqMsg;
        private Label lblReqStatus;

        // Settings Controls
        private TextBox txtSetStation;
        private TextBox txtSetStream;
        private TextBox txtSetApi;
        private CheckBox chkSetAutoPlay;
        private CheckBox chkSetMinTray;

        // Visualizer State
        private int[] barHeights = new int[24];
        private Random random = new Random();
        private float vinylAngle = 0f;

        // Theme Palette
        private System.Drawing.Color colBg = System.Drawing.Color.FromArgb(11, 15, 25);
        private System.Drawing.Color colCard = System.Drawing.Color.FromArgb(21, 29, 46);
        private System.Drawing.Color colBorder = System.Drawing.Color.FromArgb(35, 48, 72);
        private System.Drawing.Color colPrimary = System.Drawing.Color.FromArgb(59, 130, 246);
        private System.Drawing.Color colAccent = System.Drawing.Color.FromArgb(16, 185, 129);
        private System.Drawing.Color colDanger = System.Drawing.Color.FromArgb(239, 68, 68);
        private System.Drawing.Color colTextMain = System.Drawing.Color.FromArgb(248, 250, 252);
        private System.Drawing.Color colTextMuted = System.Drawing.Color.FromArgb(148, 163, 184);

        public MainWindow()
        {
            configPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "config.json");
            LoadConfig();

            InitializeWindow();
            InitializeAudioPlayer();
            InitializeTray();
            InitializeTabs();
            InitializeTimers();

            if (config.auto_play)
            {
                TogglePlay();
            }

            PollTelemetry();
        }

        private void LoadConfig()
        {
            try
            {
                if (File.Exists(configPath))
                {
                    string json = File.ReadAllText(configPath);
                    var serializer = new JavaScriptSerializer();
                    config = serializer.Deserialize<AppConfig>(json) ?? new AppConfig();
                }
                else
                {
                    config = new AppConfig();
                    SaveConfig();
                }
            }
            catch
            {
                config = new AppConfig();
            }
        }

        private void SaveConfig()
        {
            try
            {
                var serializer = new JavaScriptSerializer();
                string json = serializer.Serialize(config);
                File.WriteAllText(configPath, json);
            }
            catch {}
        }

        private void InitializeWindow()
        {
            this.Text = config.station_name + " - Desktop Broadcast Player";
            this.Size = new Size(980, 680);
            this.MinimumSize = new Size(840, 580);
            this.StartPosition = FormStartPosition.CenterScreen;
            this.BackColor = colBg;
            this.ForeColor = colTextMain;
            this.Font = new Font("Segoe UI", 9.5f, FontStyle.Regular);

            // Generate crisp broadcast icon
            Bitmap bmp = new Bitmap(32, 32);
            using (Graphics g = Graphics.FromImage(bmp))
            {
                g.SmoothingMode = SmoothingMode.AntiAlias;
                g.Clear(System.Drawing.Color.Transparent);
                using (System.Drawing.Brush b = new SolidBrush(colPrimary))
                {
                    g.FillEllipse(b, 2, 2, 28, 28);
                }
                using (System.Drawing.Pen p = new System.Drawing.Pen(System.Drawing.Color.White, 2.5f))
                {
                    g.DrawArc(p, 6, 6, 20, 20, 210, 120);
                    g.DrawArc(p, 9, 9, 14, 14, 210, 120);
                }
                using (System.Drawing.Brush b = new SolidBrush(System.Drawing.Color.White))
                {
                    g.FillEllipse(b, 13, 13, 6, 6);
                }
            }
            this.Icon = Icon.FromHandle(bmp.GetHicon());

            this.FormClosing += new FormClosingEventHandler(MainWindow_FormClosing);
        }

        private void InitializeAudioPlayer()
        {
            mediaPlayer = new MediaPlayer();
            mediaPlayer.Volume = config.volume / 100.0;
            mediaPlayer.MediaOpened += (s, e) => {
                lblStatusBadge.Text = "● LIVE ON AIR";
                lblStatusBadge.ForeColor = colAccent;
            };
            mediaPlayer.MediaFailed += (s, e) => {
                lblStatusBadge.Text = "● CONNECTION ERROR";
                lblStatusBadge.ForeColor = colDanger;
                StopPlayback();
            };
            mediaPlayer.MediaEnded += (s, e) => {
                // If live stream stops or drops, auto reconnect
                if (isPlaying)
                {
                    StartPlayback();
                }
            };
        }

        private void InitializeTray()
        {
            trayMenu = new ContextMenuStrip();
            trayMenu.Items.Add("▶ Play Stream", null, (s, e) => TogglePlay());
            trayMenu.Items.Add("⏹ Stop Stream", null, (s, e) => StopPlayback());
            trayMenu.Items.Add(new ToolStripSeparator());
            trayMenu.Items.Add("Open Radio Window", null, (s, e) => ShowFromTray());
            trayMenu.Items.Add("Web Portal", null, (s, e) => {
                ShowFromTray();
                mainTabControl.SelectedTab = tabBrowser;
                webBrowser.Navigate(config.api_url);
            });
            trayMenu.Items.Add("Admin Studio", null, (s, e) => {
                ShowFromTray();
                mainTabControl.SelectedTab = tabBrowser;
                webBrowser.Navigate(config.api_url + "/admin");
            });
            trayMenu.Items.Add(new ToolStripSeparator());
            trayMenu.Items.Add("Exit", null, (s, e) => {
                config.minimize_to_tray = false;
                Application.Exit();
            });

            trayIcon = new NotifyIcon();
            trayIcon.Text = config.station_name + " Player";
            trayIcon.Icon = this.Icon;
            trayIcon.ContextMenuStrip = trayMenu;
            trayIcon.Visible = true;
            trayIcon.DoubleClick += (s, e) => ShowFromTray();
        }

        private void InitializeTabs()
        {
            // Top Header Bar
            Panel topBar = new Panel();
            topBar.Dock = DockStyle.Top;
            topBar.Height = 65;
            topBar.BackColor = colCard;
            topBar.Padding = new Padding(20, 10, 20, 10);
            this.Controls.Add(topBar);

            Label lblLogo = new Label();
            lblLogo.Text = config.station_name;
            lblLogo.Font = new Font("Segoe UI", 16f, FontStyle.Bold);
            lblLogo.ForeColor = colTextMain;
            lblLogo.AutoSize = true;
            lblLogo.Location = new Point(15, 16);
            topBar.Controls.Add(lblLogo);

            Label lblSubLogo = new Label();
            lblSubLogo.Text = "Self-Hosted Broadcast Platform";
            lblSubLogo.Font = new Font("Segoe UI", 8.5f, FontStyle.Regular);
            lblSubLogo.ForeColor = colTextMuted;
            lblSubLogo.AutoSize = true;
            lblSubLogo.Location = new Point(18, 42);
            topBar.Controls.Add(lblSubLogo);

            lblStatusBadge = new Label();
            lblStatusBadge.Text = "● STANDBY";
            lblStatusBadge.Font = new Font("Segoe UI", 9.5f, FontStyle.Bold);
            lblStatusBadge.ForeColor = colTextMuted;
            lblStatusBadge.AutoSize = true;
            lblStatusBadge.Location = new Point(topBar.Width - 160, 22);
            lblStatusBadge.Anchor = AnchorStyles.Top | AnchorStyles.Right;
            topBar.Controls.Add(lblStatusBadge);

            // Tab Control
            mainTabControl = new TabControl();
            mainTabControl.Dock = DockStyle.Fill;
            mainTabControl.Font = new Font("Segoe UI", 10f, FontStyle.Regular);
            this.Controls.Add(mainTabControl);

            // Tab 1: Live Player
            tabPlayer = new TabPage("  📻 Live Player  ");
            tabPlayer.BackColor = colBg;
            BuildPlayerTab();
            mainTabControl.TabPages.Add(tabPlayer);

            // Tab 2: Web Studio
            tabBrowser = new TabPage("  🌐 Web Studio  ");
            tabBrowser.BackColor = colBg;
            BuildBrowserTab();
            mainTabControl.TabPages.Add(tabBrowser);

            // Tab 3: Song Request
            tabRequest = new TabPage("  💌 Song Request  ");
            tabRequest.BackColor = colBg;
            BuildRequestTab();
            mainTabControl.TabPages.Add(tabRequest);

            // Tab 4: Settings
            tabSettings = new TabPage("  ⚙️ Settings  ");
            tabSettings.BackColor = colBg;
            BuildSettingsTab();
            mainTabControl.TabPages.Add(tabSettings);
        }

        private void BuildPlayerTab()
        {
            Panel card = new Panel();
            card.Anchor = AnchorStyles.Top | AnchorStyles.Left | AnchorStyles.Right | AnchorStyles.Bottom;
            card.Location = new Point(25, 20);
            card.Size = new Size(tabPlayer.Width - 50, tabPlayer.Height - 40);
            card.BackColor = colCard;
            card.Paint += (s, e) => {
                using (System.Drawing.Pen p = new System.Drawing.Pen(colBorder, 1))
                {
                    e.Graphics.DrawRectangle(p, 0, 0, card.Width - 1, card.Height - 1);
                }
            };
            tabPlayer.Controls.Add(card);

            // Vinyl Panel
            Panel vinylPanel = new Panel();
            vinylPanel.Size = new Size(200, 200);
            vinylPanel.Location = new Point(40, 40);
            vinylPanel.Paint += new PaintEventHandler(VinylPanel_Paint);
            card.Controls.Add(vinylPanel);

            // Spectrum Visualizer
            visualizerPanel = new Panel();
            visualizerPanel.Size = new Size(200, 36);
            visualizerPanel.Location = new Point(40, 255);
            visualizerPanel.Paint += new PaintEventHandler(VisualizerPanel_Paint);
            card.Controls.Add(visualizerPanel);

            // Metadata Labels
            Label lblNowTag = new Label();
            lblNowTag.Text = "NOW PLAYING";
            lblNowTag.Font = new Font("Segoe UI", 8.5f, FontStyle.Bold);
            lblNowTag.ForeColor = colPrimary;
            lblNowTag.Location = new Point(275, 45);
            lblNowTag.AutoSize = true;
            card.Controls.Add(lblNowTag);

            lblTrackTitle = new Label();
            lblTrackTitle.Text = "Radio Agro Broadcast";
            lblTrackTitle.Font = new Font("Segoe UI", 16f, FontStyle.Bold);
            lblTrackTitle.ForeColor = colTextMain;
            lblTrackTitle.Location = new Point(273, 68);
            lblTrackTitle.Size = new Size(card.Width - 300, 38);
            lblTrackTitle.Anchor = AnchorStyles.Top | AnchorStyles.Left | AnchorStyles.Right;
            card.Controls.Add(lblTrackTitle);

            lblTrackArtist = new Label();
            lblTrackArtist.Text = "Connecting to live stream...";
            lblTrackArtist.Font = new Font("Segoe UI", 12f, FontStyle.Regular);
            lblTrackArtist.ForeColor = colTextMuted;
            lblTrackArtist.Location = new Point(275, 108);
            lblTrackArtist.Size = new Size(card.Width - 300, 28);
            lblTrackArtist.Anchor = AnchorStyles.Top | AnchorStyles.Left | AnchorStyles.Right;
            card.Controls.Add(lblTrackArtist);

            // Chips / Badges
            lblMode = new Label();
            lblMode.Text = "MODE: AUTO DJ";
            lblMode.Font = new Font("Segoe UI", 8.5f, FontStyle.Bold);
            lblMode.BackColor = System.Drawing.Color.FromArgb(30, 41, 59);
            lblMode.ForeColor = colPrimary;
            lblMode.Padding = new Padding(6, 4, 6, 4);
            lblMode.Location = new Point(275, 150);
            lblMode.AutoSize = true;
            card.Controls.Add(lblMode);

            lblListeners = new Label();
            lblListeners.Text = "👥 0 LISTENERS";
            lblListeners.Font = new Font("Segoe UI", 8.5f, FontStyle.Bold);
            lblListeners.BackColor = System.Drawing.Color.FromArgb(30, 41, 59);
            lblListeners.ForeColor = colAccent;
            lblListeners.Padding = new Padding(6, 4, 6, 4);
            lblListeners.Location = new Point(390, 150);
            lblListeners.AutoSize = true;
            card.Controls.Add(lblListeners);

            lblBitrate = new Label();
            lblBitrate.Text = "⚡ 128 KBPS";
            lblBitrate.Font = new Font("Segoe UI", 8.5f, FontStyle.Bold);
            lblBitrate.BackColor = System.Drawing.Color.FromArgb(30, 41, 59);
            lblBitrate.ForeColor = colTextMuted;
            lblBitrate.Padding = new Padding(6, 4, 6, 4);
            lblBitrate.Location = new Point(515, 150);
            lblBitrate.AutoSize = true;
            card.Controls.Add(lblBitrate);

            // Up Next Row
            Panel pnlNext = new Panel();
            pnlNext.Location = new Point(275, 190);
            pnlNext.Size = new Size(card.Width - 315, 34);
            pnlNext.BackColor = System.Drawing.Color.FromArgb(15, 23, 42);
            pnlNext.Anchor = AnchorStyles.Top | AnchorStyles.Left | AnchorStyles.Right;
            card.Controls.Add(pnlNext);

            lblNextTrack = new Label();
            lblNextTrack.Text = "⏭ Up Next: Auto DJ Smart Rotation";
            lblNextTrack.Font = new Font("Segoe UI", 9f, FontStyle.Regular);
            lblNextTrack.ForeColor = colTextMuted;
            lblNextTrack.Location = new Point(10, 8);
            lblNextTrack.AutoSize = true;
            pnlNext.Controls.Add(lblNextTrack);

            // Audio Controls Bar
            Panel pnlControls = new Panel();
            pnlControls.Location = new Point(275, 240);
            pnlControls.Size = new Size(card.Width - 315, 55);
            pnlControls.Anchor = AnchorStyles.Top | AnchorStyles.Left | AnchorStyles.Right;
            card.Controls.Add(pnlControls);

            btnPlay = new Button();
            btnPlay.Text = "▶  LISTEN LIVE";
            btnPlay.Font = new Font("Segoe UI", 10.5f, FontStyle.Bold);
            btnPlay.Size = new Size(160, 45);
            btnPlay.Location = new Point(0, 5);
            btnPlay.BackColor = colPrimary;
            btnPlay.ForeColor = System.Drawing.Color.White;
            btnPlay.FlatStyle = FlatStyle.Flat;
            btnPlay.FlatAppearance.BorderSize = 0;
            btnPlay.Cursor = Cursors.Hand;
            btnPlay.Click += (s, e) => TogglePlay();
            pnlControls.Controls.Add(btnPlay);

            Label lblVolIcon = new Label();
            lblVolIcon.Text = "🔊";
            lblVolIcon.Font = new Font("Segoe UI", 12f);
            lblVolIcon.Location = new Point(190, 16);
            lblVolIcon.AutoSize = true;
            pnlControls.Controls.Add(lblVolIcon);

            volumeBar = new TrackBar();
            volumeBar.Minimum = 0;
            volumeBar.Maximum = 100;
            volumeBar.Value = config.volume;
            volumeBar.TickStyle = TickStyle.None;
            volumeBar.Size = new Size(140, 35);
            volumeBar.Location = new Point(220, 15);
            volumeBar.Scroll += new EventHandler(VolumeBar_Scroll);
            pnlControls.Controls.Add(volumeBar);

            lblVolume = new Label();
            lblVolume.Text = config.volume + "%";
            lblVolume.Font = new Font("Segoe UI", 9f, FontStyle.Regular);
            lblVolume.ForeColor = colTextMuted;
            lblVolume.Location = new Point(365, 18);
            lblVolume.AutoSize = true;
            pnlControls.Controls.Add(lblVolume);
        }

        private void BuildBrowserTab()
        {
            Panel navBar = new Panel();
            navBar.Dock = DockStyle.Top;
            navBar.Height = 45;
            navBar.BackColor = colCard;
            navBar.Padding = new Padding(10, 6, 10, 6);
            tabBrowser.Controls.Add(navBar);

            Button btnPortal = new Button();
            btnPortal.Text = "Public Portal";
            btnPortal.Size = new Size(110, 32);
            btnPortal.Location = new Point(10, 6);
            btnPortal.BackColor = colPrimary;
            btnPortal.ForeColor = System.Drawing.Color.White;
            btnPortal.FlatStyle = FlatStyle.Flat;
            btnPortal.FlatAppearance.BorderSize = 0;
            btnPortal.Click += (s, e) => webBrowser.Navigate(config.api_url);
            navBar.Controls.Add(btnPortal);

            Button btnAdmin = new Button();
            btnAdmin.Text = "Admin Studio";
            btnAdmin.Size = new Size(110, 32);
            btnAdmin.Location = new Point(130, 6);
            btnAdmin.BackColor = colBorder;
            btnAdmin.ForeColor = System.Drawing.Color.White;
            btnAdmin.FlatStyle = FlatStyle.Flat;
            btnAdmin.FlatAppearance.BorderSize = 0;
            btnAdmin.Click += (s, e) => webBrowser.Navigate(config.api_url + "/admin");
            navBar.Controls.Add(btnAdmin);

            Button btnRefresh = new Button();
            btnRefresh.Text = "🔄 Refresh";
            btnRefresh.Size = new Size(90, 32);
            btnRefresh.Location = new Point(250, 6);
            btnRefresh.BackColor = colBorder;
            btnRefresh.ForeColor = System.Drawing.Color.White;
            btnRefresh.FlatStyle = FlatStyle.Flat;
            btnRefresh.FlatAppearance.BorderSize = 0;
            btnRefresh.Click += (s, e) => webBrowser.Refresh();
            navBar.Controls.Add(btnRefresh);

            webBrowser = new WebBrowser();
            webBrowser.Dock = DockStyle.Fill;
            webBrowser.ScriptErrorsSuppressed = true;
            tabBrowser.Controls.Add(webBrowser);
            webBrowser.BringToFront();

            // Navigate on first load
            tabBrowser.Enter += (s, e) => {
                if (webBrowser.Url == null)
                {
                    webBrowser.Navigate(config.api_url);
                }
            };
        }

        private void BuildRequestTab()
        {
            Panel card = new Panel();
            card.Size = new Size(550, 420);
            card.Location = new Point(35, 25);
            card.BackColor = colCard;
            card.Padding = new Padding(25);
            tabRequest.Controls.Add(card);

            Label lblTitle = new Label();
            lblTitle.Text = "Submit Song Request";
            lblTitle.Font = new Font("Segoe UI", 14f, FontStyle.Bold);
            lblTitle.ForeColor = colTextMain;
            lblTitle.Location = new Point(25, 20);
            lblTitle.AutoSize = true;
            card.Controls.Add(lblTitle);

            Label lblSub = new Label();
            lblSub.Text = "Send your dedication and song request to the live broadcast queue";
            lblSub.Font = new Font("Segoe UI", 9f);
            lblSub.ForeColor = colTextMuted;
            lblSub.Location = new Point(26, 52);
            lblSub.AutoSize = true;
            card.Controls.Add(lblSub);

            // Name
            Label lblN = new Label();
            lblN.Text = "YOUR NAME";
            lblN.Font = new Font("Segoe UI", 8.5f, FontStyle.Bold);
            lblN.ForeColor = colTextMuted;
            lblN.Location = new Point(25, 90);
            lblN.AutoSize = true;
            card.Controls.Add(lblN);

            txtReqName = new TextBox();
            txtReqName.Location = new Point(25, 112);
            txtReqName.Size = new Size(500, 28);
            txtReqName.BackColor = System.Drawing.Color.FromArgb(15, 23, 42);
            txtReqName.ForeColor = System.Drawing.Color.White;
            txtReqName.BorderStyle = BorderStyle.FixedSingle;
            card.Controls.Add(txtReqName);

            // Song Title
            Label lblS = new Label();
            lblS.Text = "SONG TITLE / ARTIST";
            lblS.Font = new Font("Segoe UI", 8.5f, FontStyle.Bold);
            lblS.ForeColor = colTextMuted;
            lblS.Location = new Point(25, 150);
            lblS.AutoSize = true;
            card.Controls.Add(lblS);

            txtReqSong = new TextBox();
            txtReqSong.Location = new Point(25, 172);
            txtReqSong.Size = new Size(500, 28);
            txtReqSong.BackColor = System.Drawing.Color.FromArgb(15, 23, 42);
            txtReqSong.ForeColor = System.Drawing.Color.White;
            txtReqSong.BorderStyle = BorderStyle.FixedSingle;
            card.Controls.Add(txtReqSong);

            // Message
            Label lblM = new Label();
            lblM.Text = "MESSAGE / DEDICATION";
            lblM.Font = new Font("Segoe UI", 8.5f, FontStyle.Bold);
            lblM.ForeColor = colTextMuted;
            lblM.Location = new Point(25, 210);
            lblM.AutoSize = true;
            card.Controls.Add(lblM);

            txtReqMsg = new TextBox();
            txtReqMsg.Location = new Point(25, 232);
            txtReqMsg.Size = new Size(500, 60);
            txtReqMsg.Multiline = true;
            txtReqMsg.BackColor = System.Drawing.Color.FromArgb(15, 23, 42);
            txtReqMsg.ForeColor = System.Drawing.Color.White;
            txtReqMsg.BorderStyle = BorderStyle.FixedSingle;
            card.Controls.Add(txtReqMsg);

            Button btnSubmit = new Button();
            btnSubmit.Text = "Send Song Request";
            btnSubmit.Font = new Font("Segoe UI", 10f, FontStyle.Bold);
            btnSubmit.Size = new Size(180, 38);
            btnSubmit.Location = new Point(25, 305);
            btnSubmit.BackColor = colPrimary;
            btnSubmit.ForeColor = System.Drawing.Color.White;
            btnSubmit.FlatStyle = FlatStyle.Flat;
            btnSubmit.FlatAppearance.BorderSize = 0;
            btnSubmit.Cursor = Cursors.Hand;
            btnSubmit.Click += new EventHandler(BtnSubmitRequest_Click);
            card.Controls.Add(btnSubmit);

            lblReqStatus = new Label();
            lblReqStatus.Location = new Point(220, 315);
            lblReqStatus.Size = new Size(300, 25);
            lblReqStatus.ForeColor = colAccent;
            lblReqStatus.AutoSize = true;
            card.Controls.Add(lblReqStatus);
        }

        private void BuildSettingsTab()
        {
            Panel card = new Panel();
            card.Size = new Size(600, 450);
            card.Location = new Point(35, 25);
            card.BackColor = colCard;
            card.Padding = new Padding(25);
            tabSettings.Controls.Add(card);

            Label lblTitle = new Label();
            lblTitle.Text = "Desktop Player Settings";
            lblTitle.Font = new Font("Segoe UI", 14f, FontStyle.Bold);
            lblTitle.ForeColor = colTextMain;
            lblTitle.Location = new Point(25, 20);
            lblTitle.AutoSize = true;
            card.Controls.Add(lblTitle);

            // Station Name
            Label lbl1 = new Label();
            lbl1.Text = "STATION NAME";
            lbl1.Font = new Font("Segoe UI", 8.5f, FontStyle.Bold);
            lbl1.ForeColor = colTextMuted;
            lbl1.Location = new Point(25, 65);
            lbl1.AutoSize = true;
            card.Controls.Add(lbl1);

            txtSetStation = new TextBox();
            txtSetStation.Text = config.station_name;
            txtSetStation.Location = new Point(25, 87);
            txtSetStation.Size = new Size(540, 28);
            txtSetStation.BackColor = System.Drawing.Color.FromArgb(15, 23, 42);
            txtSetStation.ForeColor = System.Drawing.Color.White;
            txtSetStation.BorderStyle = BorderStyle.FixedSingle;
            card.Controls.Add(txtSetStation);

            // Stream URL
            Label lbl2 = new Label();
            lbl2.Text = "ICECAST STREAM URL (MP3)";
            lbl2.Font = new Font("Segoe UI", 8.5f, FontStyle.Bold);
            lbl2.ForeColor = colTextMuted;
            lbl2.Location = new Point(25, 125);
            lbl2.AutoSize = true;
            card.Controls.Add(lbl2);

            txtSetStream = new TextBox();
            txtSetStream.Text = config.stream_url;
            txtSetStream.Location = new Point(25, 147);
            txtSetStream.Size = new Size(540, 28);
            txtSetStream.BackColor = System.Drawing.Color.FromArgb(15, 23, 42);
            txtSetStream.ForeColor = System.Drawing.Color.White;
            txtSetStream.BorderStyle = BorderStyle.FixedSingle;
            card.Controls.Add(txtSetStream);

            // API URL
            Label lbl3 = new Label();
            lbl3.Text = "WEB / REST API BASE URL";
            lbl3.Font = new Font("Segoe UI", 8.5f, FontStyle.Bold);
            lbl3.ForeColor = colTextMuted;
            lbl3.Location = new Point(25, 185);
            lbl3.AutoSize = true;
            card.Controls.Add(lbl3);

            txtSetApi = new TextBox();
            txtSetApi.Text = config.api_url;
            txtSetApi.Location = new Point(25, 207);
            txtSetApi.Size = new Size(540, 28);
            txtSetApi.BackColor = System.Drawing.Color.FromArgb(15, 23, 42);
            txtSetApi.ForeColor = System.Drawing.Color.White;
            txtSetApi.BorderStyle = BorderStyle.FixedSingle;
            card.Controls.Add(txtSetApi);

            // Checkboxes
            chkSetAutoPlay = new CheckBox();
            chkSetAutoPlay.Text = "Auto-start live stream playback when app launches";
            chkSetAutoPlay.Checked = config.auto_play;
            chkSetAutoPlay.Location = new Point(25, 250);
            chkSetAutoPlay.AutoSize = true;
            card.Controls.Add(chkSetAutoPlay);

            chkSetMinTray = new CheckBox();
            chkSetMinTray.Text = "Minimize to system tray when closing window";
            chkSetMinTray.Checked = config.minimize_to_tray;
            chkSetMinTray.Location = new Point(25, 280);
            chkSetMinTray.AutoSize = true;
            card.Controls.Add(chkSetMinTray);

            Button btnSave = new Button();
            btnSave.Text = "Save & Apply Settings";
            btnSave.Font = new Font("Segoe UI", 10f, FontStyle.Bold);
            btnSave.Size = new Size(180, 38);
            btnSave.Location = new Point(25, 330);
            btnSave.BackColor = colPrimary;
            btnSave.ForeColor = System.Drawing.Color.White;
            btnSave.FlatStyle = FlatStyle.Flat;
            btnSave.FlatAppearance.BorderSize = 0;
            btnSave.Cursor = Cursors.Hand;
            btnSave.Click += new EventHandler(BtnSaveSettings_Click);
            card.Controls.Add(btnSave);
        }

        private void InitializeTimers()
        {
            // Visualizer & Vinyl rotation animation (40 FPS)
            visualizerTimer = new System.Windows.Forms.Timer();
            visualizerTimer.Interval = 25;
            visualizerTimer.Tick += (s, e) => {
                if (isPlaying)
                {
                    vinylAngle += 2.5f;
                    if (vinylAngle >= 360f) vinylAngle = 0f;

                    for (int i = 0; i < barHeights.Length; i++)
                    {
                        barHeights[i] = random.Next(4, 32);
                    }
                }
                else
                {
                    for (int i = 0; i < barHeights.Length; i++)
                    {
                        if (barHeights[i] > 2) barHeights[i] -= 2;
                    }
                }

                if (tabPlayer != null && tabPlayer.Visible)
                {
                    visualizerPanel.Invalidate();
                    tabPlayer.Controls[0].Controls[0].Invalidate(); // Redraw vinyl
                }
            };
            visualizerTimer.Start();

            // Periodic Telemetry Poll (every 5 seconds)
            telemetryTimer = new System.Windows.Forms.Timer();
            telemetryTimer.Interval = 5000;
            telemetryTimer.Tick += (s, e) => PollTelemetry();
            telemetryTimer.Start();
        }

        private void TogglePlay()
        {
            if (isPlaying)
            {
                StopPlayback();
            }
            else
            {
                StartPlayback();
            }
        }

        private void StartPlayback()
        {
            try
            {
                lblStatusBadge.Text = "● CONNECTING...";
                lblStatusBadge.ForeColor = System.Drawing.Color.Orange;

                // Cache buster for live stream
                string streamWithBuster = config.stream_url + (config.stream_url.Contains("?") ? "&" : "?") + "t=" + DateTime.UtcNow.Ticks;
                mediaPlayer.Open(new Uri(streamWithBuster));
                mediaPlayer.Play();

                isPlaying = true;
                btnPlay.Text = "⏹  STOP BROADCAST";
                btnPlay.BackColor = colDanger;

                trayMenu.Items[0].Text = "⏹ Stop Stream";
            }
            catch (Exception ex)
            {
                MessageBox.Show("Failed to open stream URL: " + ex.Message, "Stream Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                StopPlayback();
            }
        }

        private void StopPlayback()
        {
            try
            {
                mediaPlayer.Stop();
                mediaPlayer.Close();
            }
            catch {}

            isPlaying = false;
            btnPlay.Text = "▶  LISTEN LIVE";
            btnPlay.BackColor = colPrimary;
            lblStatusBadge.Text = "● OFFLINE / STOPPED";
            lblStatusBadge.ForeColor = colTextMuted;

            trayMenu.Items[0].Text = "▶ Play Stream";
        }

        private void VolumeBar_Scroll(object sender, EventArgs e)
        {
            config.volume = volumeBar.Value;
            mediaPlayer.Volume = config.volume / 100.0;
            lblVolume.Text = config.volume + "%";
            SaveConfig();
        }

        private void PollTelemetry()
        {
            ThreadPool.QueueUserWorkItem((state) => {
                try
                {
                    using (WebClient client = new WebClient())
                    {
                        client.Headers[HttpRequestHeader.UserAgent] = "RadioAgro-Desktop/1.0";
                        string json = client.DownloadString(config.api_url.TrimEnd('/') + "/api/radio/status");

                        var serializer = new JavaScriptSerializer();
                        var data = serializer.Deserialize<Dictionary<string, object>>(json);

                        if (data != null)
                        {
                            this.BeginInvoke(new Action(() => {
                                UpdateTelemetryUI(data);
                            }));
                        }
                    }
                }
                catch {}
            });
        }

        private void UpdateTelemetryUI(Dictionary<string, object> data)
        {
            try
            {
                if (data.ContainsKey("now_playing") && data["now_playing"] is Dictionary<string, object>)
                {
                    var np = (Dictionary<string, object>)data["now_playing"];
                    string title = np.ContainsKey("title") ? Convert.ToString(np["title"]) : "Radio Agro Live";
                    string artist = np.ContainsKey("artist") ? Convert.ToString(np["artist"]) : "Radio Agro";

                    if (lblTrackTitle.Text != title)
                    {
                        lblTrackTitle.Text = title;
                        lblTrackArtist.Text = artist;
                        trayIcon.BalloonTipTitle = "Now Playing on " + config.station_name;
                        trayIcon.BalloonTipText = artist + " - " + title;
                        trayIcon.ShowBalloonTip(3000);
                    }
                }

                if (data.ContainsKey("listeners"))
                {
                    lblListeners.Text = "👥 " + Convert.ToString(data["listeners"]) + " LISTENERS";
                }

                if (data.ContainsKey("bitrate"))
                {
                    lblBitrate.Text = "⚡ " + Convert.ToString(data["bitrate"]) + " KBPS";
                }

                if (data.ContainsKey("mode"))
                {
                    lblMode.Text = "MODE: " + Convert.ToString(data["mode"]).ToUpper();
                }
            }
            catch {}
        }

        private void BtnSubmitRequest_Click(object sender, EventArgs e)
        {
            string name = txtReqName.Text.Trim();
            string song = txtReqSong.Text.Trim();
            string msg = txtReqMsg.Text.Trim();

            if (string.IsNullOrEmpty(name) || string.IsNullOrEmpty(song))
            {
                lblReqStatus.ForeColor = colDanger;
                lblReqStatus.Text = "Please fill in your name and song title.";
                return;
            }

            lblReqStatus.ForeColor = colPrimary;
            lblReqStatus.Text = "Submitting song request...";

            ThreadPool.QueueUserWorkItem((state) => {
                try
                {
                    using (WebClient client = new WebClient())
                    {
                        var values = new System.Collections.Specialized.NameValueCollection();
                        values["name"] = name;
                        values["song_id"] = song; // Supports song title or id
                        values["message"] = msg;

                        byte[] responseBytes = client.UploadValues(config.api_url.TrimEnd('/') + "/api/song-request", "POST", values);
                        string res = Encoding.UTF8.GetString(responseBytes);

                        this.BeginInvoke(new Action(() => {
                            lblReqStatus.ForeColor = colAccent;
                            lblReqStatus.Text = "Request sent successfully!";
                            txtReqSong.Clear();
                            txtReqMsg.Clear();
                        }));
                    }
                }
                catch (Exception ex)
                {
                    this.BeginInvoke(new Action(() => {
                        lblReqStatus.ForeColor = colDanger;
                        lblReqStatus.Text = "Error submitting: " + ex.Message;
                    }));
                }
            });
        }

        private void BtnSaveSettings_Click(object sender, EventArgs e)
        {
            config.station_name = txtSetStation.Text.Trim();
            config.stream_url = txtSetStream.Text.Trim();
            config.api_url = txtSetApi.Text.Trim();
            config.auto_play = chkSetAutoPlay.Checked;
            config.minimize_to_tray = chkSetMinTray.Checked;

            SaveConfig();
            this.Text = config.station_name + " - Desktop Broadcast Player";
            trayIcon.Text = config.station_name + " Player";

            MessageBox.Show("Settings saved successfully!", "Settings", MessageBoxButtons.OK, MessageBoxIcon.Information);
        }

        private void VinylPanel_Paint(object sender, PaintEventArgs e)
        {
            Graphics g = e.Graphics;
            g.SmoothingMode = SmoothingMode.AntiAlias;

            int size = 190;
            int cx = 100;
            int cy = 100;

            // Vinyl Outer Body
            using (System.Drawing.Brush b = new SolidBrush(System.Drawing.Color.FromArgb(15, 23, 42)))
            {
                g.FillEllipse(b, cx - size / 2, cy - size / 2, size, size);
            }

            // Concentric Vinyl Grooves
            using (System.Drawing.Pen p = new System.Drawing.Pen(System.Drawing.Color.FromArgb(30, 41, 59), 1))
            {
                g.DrawEllipse(p, cx - 80, cy - 80, 160, 160);
                g.DrawEllipse(p, cx - 65, cy - 65, 130, 130);
                g.DrawEllipse(p, cx - 50, cy - 50, 100, 100);
            }

            // Rotating Center Label
            GraphicsState state = g.Save();
            g.TranslateTransform(cx, cy);
            g.RotateTransform(vinylAngle);

            using (System.Drawing.Drawing2D.LinearGradientBrush lb = new System.Drawing.Drawing2D.LinearGradientBrush(new Point(-35, -35), new Point(35, 35), colPrimary, colAccent))
            {
                g.FillEllipse(lb, -35, -35, 70, 70);
            }
            using (System.Drawing.Brush b = new SolidBrush(System.Drawing.Color.FromArgb(11, 15, 25)))
            {
                g.FillEllipse(b, -8, -8, 16, 16);
            }

            // Disc Spindle Hole
            g.Restore(state);
        }

        private void VisualizerPanel_Paint(object sender, PaintEventArgs e)
        {
            Graphics g = e.Graphics;
            g.SmoothingMode = SmoothingMode.AntiAlias;

            int barWidth = 6;
            int gap = 2;
            int startX = 0;

            for (int i = 0; i < barHeights.Length; i++)
            {
                int h = barHeights[i];
                int x = startX + i * (barWidth + gap);
                int y = visualizerPanel.Height - h;

                System.Drawing.Color barColor = (i % 2 == 0) ? colPrimary : colAccent;
                using (System.Drawing.Brush b = new SolidBrush(barColor))
                {
                    g.FillRectangle(b, x, y, barWidth, h);
                }
            }
        }

        private void MainWindow_FormClosing(object sender, FormClosingEventArgs e)
        {
            if (config.minimize_to_tray && e.CloseReason == CloseReason.UserClosing)
            {
                e.Cancel = true;
                this.Hide();
                trayIcon.ShowBalloonTip(2000, config.station_name, "Radio player is still streaming in the background.", ToolTipIcon.Info);
            }
            else
            {
                StopPlayback();
                trayIcon.Visible = false;
            }
        }

        private void ShowFromTray()
        {
            this.Show();
            this.WindowState = FormWindowState.Normal;
            this.BringToFront();
        }
    }
}
