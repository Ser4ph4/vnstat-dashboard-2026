<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['2fa_status']) || $_SESSION['2fa_status'] !== 'confirmed') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>VnStat · Dashboard</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="/libs/fontawesome/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&family=Syne:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="/libs/tailwind.min.js"></script>
    <script src="/libs/react.production.min.js"></script>
    <script src="/libs/react-dom.production.min.js"></script>
    <script src="/libs/babel.min.js"></script>
    <script src="/libs/prop-types.min.js"></script>
    <script src="/libs/Recharts.js"></script>
    <style>
        :root {
            --bg-void: #050810;
            --bg-deep: #080d1a;
            --bg-surface: #0d1425;
            --bg-raised: #131c2e;
            --bg-hover: #1a2438;
            --border-dim: rgba(99,140,210,0.08);
            --border-glow: rgba(56,189,248,0.25);
            --accent-sky: #38bdf8;
            --accent-teal: #2dd4bf;
            --accent-violet: #a78bfa;
            --accent-rose: #fb7185;
            --accent-amber: #fbbf24;
            --text-bright: #f0f6ff;
            --text-mid: #8ba3cc;
            --text-dim: #3d5273;
            --font-display: 'Syne', sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        * { box-sizing: border-box; }

        html { scroll-behavior: smooth; font-size: 16px; }

        body {
            font-family: var(--font-display);
            background-color: var(--bg-void);
            background-image:
                radial-gradient(ellipse 80% 50% at 20% -10%, rgba(56,189,248,0.06) 0%, transparent 60%),
                radial-gradient(ellipse 60% 40% at 80% 100%, rgba(167,139,250,0.04) 0%, transparent 60%),
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='60'%3E%3Cpath d='M0 0h60v60H0z' fill='none'/%3E%3Cpath d='M0 0h1v1H0zM30 0h1v1H30zM0 30h1v1H0zM30 30h1v1H30z' fill='rgba(56,189,248,0.03)'/%3E%3C/svg%3E");
            min-height: 100vh;
            color: var(--text-bright);
        }

        /* Glass panels */
        .panel {
            background: var(--bg-surface);
            border: 1px solid var(--border-dim);
            border-radius: 16px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .panel:hover {
            border-color: var(--border-glow);
            box-shadow: 0 0 30px rgba(56,189,248,0.05), inset 0 1px 0 rgba(255,255,255,0.03);
        }
        .panel-flat {
            background: var(--bg-raised);
            border: 1px solid var(--border-dim);
            border-radius: 12px;
        }

        /* Typography */
        .mono { font-family: var(--font-mono); }
        .heading { font-family: var(--font-display); font-weight: 800; letter-spacing: -0.03em; }
        .subheading { font-family: var(--font-display); font-weight: 600; letter-spacing: -0.01em; }

        /* Status dot */
        @keyframes ping-slow {
            75%, 100% { transform: scale(1.8); opacity: 0; }
        }
        .status-dot { position: relative; display: inline-flex; }
        .status-dot::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: #4ade80;
            animation: ping-slow 2s cubic-bezier(0, 0, 0.2, 1) infinite;
        }
        .status-dot-inner { width: 7px; height: 7px; background: #4ade80; border-radius: 50%; position: relative; z-index: 1; }

        /* Tabs */
        .tab-active {
            background: linear-gradient(135deg, rgba(56,189,248,0.15), rgba(45,212,191,0.1));
            border: 1px solid rgba(56,189,248,0.3);
            color: var(--accent-sky);
            box-shadow: 0 0 20px rgba(56,189,248,0.1);
        }
        .tab-inactive {
            color: var(--text-mid);
            border: 1px solid transparent;
        }
        .tab-inactive:hover {
            color: var(--text-bright);
            background: var(--bg-hover);
            border-color: var(--border-dim);
        }

        /* Stat cards */
        .stat-dl { border-left: 3px solid #4ade80; }
        .stat-ul { border-left: 3px solid var(--accent-sky); }

        /* Progress bar track */
        .progress-track { background: rgba(99,140,210,0.1); border-radius: 9999px; overflow: hidden; }
        .progress-fill-dl { background: linear-gradient(90deg, #4ade80, #34d399); }
        .progress-fill-ul { background: linear-gradient(90deg, var(--accent-sky), var(--accent-teal)); }

        /* Tables */
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table thead th {
            font-family: var(--font-mono);
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--text-dim);
            padding: 0 14px 14px;
            border-bottom: 1px solid var(--border-dim);
        }
        .data-table tbody tr {
            transition: background 0.15s ease;
        }
        .data-table tbody tr:hover { background: var(--bg-hover); }
        .data-table td {
            padding: 13px 14px;
            font-size: 15px;
            border-bottom: 1px solid rgba(99,140,210,0.04);
        }
        .data-table tbody tr:last-child td { border-bottom: none; }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border-dim); border-radius: 2px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--border-glow); }

        /* Footer */
        .footer-bar {
            background: rgba(8,13,26,0.92);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid var(--border-dim);
        }

        /* Chip labels */
        .chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 9999px;
            font-family: var(--font-mono);
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.05em;
        }
        .chip-sky { background: rgba(56,189,248,0.1); border: 1px solid rgba(56,189,248,0.2); color: var(--accent-sky); }
        .chip-green { background: rgba(74,222,128,0.1); border: 1px solid rgba(74,222,128,0.2); color: #4ade80; }
        .chip-violet { background: rgba(167,139,250,0.1); border: 1px solid rgba(167,139,250,0.2); color: var(--accent-violet); }
        .chip-amber { background: rgba(251,191,36,0.1); border: 1px solid rgba(251,191,36,0.2); color: var(--accent-amber); }
        .chip-rose { background: rgba(251,113,133,0.1); border: 1px solid rgba(251,113,133,0.2); color: var(--accent-rose); }

        /* Metric value */
        .metric-val { font-family: var(--font-mono); font-weight: 600; font-variant-numeric: tabular-nums; }

        /* Entry animations */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-in { animation: fadeUp 0.4s ease forwards; }
        .delay-1 { animation-delay: 0.05s; opacity: 0; }
        .delay-2 { animation-delay: 0.10s; opacity: 0; }
        .delay-3 { animation-delay: 0.15s; opacity: 0; }
        .delay-4 { animation-delay: 0.20s; opacity: 0; }

        /* Glow text */
        .glow-sky { color: var(--accent-sky); text-shadow: 0 0 20px rgba(56,189,248,0.4); }
        .glow-teal { color: var(--accent-teal); text-shadow: 0 0 20px rgba(45,212,191,0.4); }

        /* Badge record */
        .badge-record {
            position: absolute;
            top: 10px; right: 10px;
            font-family: var(--font-mono);
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 0.08em;
            padding: 3px 8px;
            border-radius: 4px;
            background: rgba(251,191,36,0.15);
            border: 1px solid rgba(251,191,36,0.3);
            color: var(--accent-amber);
            text-transform: uppercase;
        }

        /* Recharts overrides */
        .recharts-tooltip-wrapper { outline: none; }
        .recharts-cartesian-axis-tick-value { font-family: var(--font-mono) !important; font-size: 12px !important; }
        .recharts-legend-item-text { font-family: var(--font-display) !important; font-size: 14px !important; }

        /* Month card top bar */
        .month-top { height: 3px; width: 100%; border-radius: 2px 2px 0 0; }

        /* Spinning refresh icon */
        .spin { animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
<div id="root"></div>

<script type="text/babel">
const { useState, useEffect, useMemo, useCallback, useRef } = React;
const {
    LineChart, Line, BarChart, Bar, AreaChart, Area,
    XAxis, YAxis, CartesianGrid, Tooltip, Legend,
    ResponsiveContainer, PieChart, Pie, Cell, ReferenceLine
} = Recharts;

/* ─── Utils ──────────────────────────────────────────────── */
const fmt = (bytes, dec = 1) => {
    if (!bytes || isNaN(bytes) || bytes === 0) return '0 B';
    const k = 1024;
    const s = ['B','KB','MB','GB','TB','PB'];
    const i = Math.min(Math.floor(Math.log(bytes) / Math.log(k)), s.length - 1);
    return `${(bytes / Math.pow(k, i)).toFixed(dec)} ${s[i]}`;
};
const fmtDate = ts => new Date(ts * 1000).toLocaleDateString('pt-BR');
const fmtTime = ts => new Date(ts * 1000).toLocaleTimeString('pt-BR', { hour:'2-digit', minute:'2-digit' });
const fmtUptime = s => {
    if (!s) return '—';
    return `${Math.floor(s/86400)}d ${Math.floor((s%86400)/3600)}h`;
};
const clamp = (v, min, max) => Math.min(Math.max(v, min), max);

/* ─── Tooltip customizado ────────────────────────────────── */
const ChartTooltip = ({ active, payload, label }) => {
    if (!active || !payload?.length) return null;
    return (
        <div style={{ background:'#0d1425', border:'1px solid rgba(56,189,248,0.25)', borderRadius:10, padding:'12px 16px', fontFamily:'JetBrains Mono, monospace', fontSize:13 }}>
            <p style={{ color:'#8ba3cc', marginBottom:6, fontSize:11, letterSpacing:'0.08em', textTransform:'uppercase' }}>{label}</p>
            {payload.map((p, i) => (
                <div key={i} style={{ display:'flex', justifyContent:'space-between', gap:20, marginBottom:3 }}>
                    <span style={{ color: p.color }}>{p.name}</span>
                    <span style={{ color:'#f0f6ff', fontWeight:600 }}>{fmt(p.value)}</span>
                </div>
            ))}
        </div>
    );
};

const CHART_GRID = { strokeDasharray:'3 3', stroke:'rgba(99,140,210,0.08)' };
const AXIS_STYLE = { stroke:'#3d5273', fontSize:12, fontFamily:'JetBrains Mono, monospace', tickLine:false };
const COLORS = { dl:'#4ade80', ul:'#38bdf8', total:'#a78bfa', accent:'#2dd4bf' };

/* ─── MAIN APP ───────────────────────────────────────────── */
const App = () => {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [tab, setTab] = useState('summary');
    const [lastUpdate, setLastUpdate] = useState(null);
    const [error, setError] = useState(null);
    const [autoRefresh, setAutoRefresh] = useState(true);
    const [loadMs, setLoadMs] = useState(0);
    const [sysStats, setSysStats] = useState({ dbSize:0, uptime:0, memory:{ total:0, used:0 } });

    const CANDIDATES = [
        'vnstat_dump_eth0.json','vnstat_dump_wlx503eaa8913bf.json','vnstat_dump_wlan0.json',
        'vnstat_dump_enp0s3.json','vnstat_dump_eno1.json','vnstat_dump_wlan1.json'
    ];

    const fetchData = useCallback(async () => {
        const t0 = performance.now();
        setError(null);
        try {
            for (const file of CANDIDATES) {
                try {
                    const res = await fetch(`${file}?_=${Date.now()}`, {
                        cache:'no-store',
                        headers:{ 'Cache-Control':'no-cache, no-store, must-revalidate', Pragma:'no-cache', Expires:'0' }
                    });
                    if (!res.ok) continue;
                    const json = await res.json();
                    setData(json);
                    setLastUpdate(new Date());
                    setLoading(false);
                    setLoadMs(Math.round(performance.now() - t0));

                    try {
                        const sr = await fetch('api_stats.php', { cache:'no-store' });
                        if (sr.ok) {
                            const sd = await sr.json();
                            if (sd.success) setSysStats({
                                dbSize: sd.data.database.size,
                                uptime: sd.data.system.uptime,
                                memory: sd.data.system.memory
                            });
                        }
                    } catch(_) {}
                    return;
                } catch(_) { continue; }
            }
            throw new Error('Nenhum arquivo JSON do vnstat encontrado.');
        } catch (e) {
            setError(e.message);
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchData();
        if (!autoRefresh) return;
        const id = setInterval(fetchData, 5 * 60 * 1000);
        return () => clearInterval(id);
    }, [autoRefresh, fetchData]);

    /* ── Loading / Error ── */
    if (loading || !data) return (
        <div style={{ minHeight:'100vh', display:'flex', alignItems:'center', justifyContent:'center' }}>
            {!error ? (
                <div style={{ textAlign:'center' }}>
                    <div style={{ width:48, height:48, border:'2px solid rgba(56,189,248,0.15)', borderTop:'2px solid #38bdf8', borderRadius:'50%', margin:'0 auto 16px', animation:'spin 0.8s linear infinite' }}></div>
                    <p style={{ color:'#8ba3cc', fontFamily:'JetBrains Mono, monospace', fontSize:14, letterSpacing:'0.1em' }}>CARREGANDO DADOS...</p>
                </div>
            ) : (
                <div className="panel" style={{ maxWidth:520, padding:32, margin:24, textAlign:'center' }}>
                    <div style={{ fontSize:40, marginBottom:16 }}>⚠</div>
                    <h2 style={{ color:'#fb7185', fontFamily:'Syne, sans-serif', fontWeight:700, fontSize:20, marginBottom:12 }}>Erro ao Carregar</h2>
                    <p style={{ color:'#8ba3cc', fontSize:15, marginBottom:20 }}>{error}</p>
                    <div className="panel-flat" style={{ padding:16, textAlign:'left', marginBottom:20 }}>
                        <p style={{ fontFamily:'JetBrains Mono, monospace', fontSize:11, color:'#3d5273', letterSpacing:'0.1em', marginBottom:10, textTransform:'uppercase' }}>Diagnóstico</p>
                        <ol style={{ color:'#8ba3cc', fontSize:13, lineHeight:1.8, paddingLeft:18 }}>
                            <li>Execute: <code style={{ color:'#38bdf8', background:'rgba(56,189,248,0.08)', padding:'1px 6px', borderRadius:4 }}>sudo php /var/www/rede/update_data.php</code></li>
                            <li>Verifique: <code style={{ color:'#38bdf8', background:'rgba(56,189,248,0.08)', padding:'1px 6px', borderRadius:4 }}>ls -la /var/www/rede/*.json</code></li>
                            <li>Permissões: <code style={{ color:'#38bdf8', background:'rgba(56,189,248,0.08)', padding:'1px 6px', borderRadius:4 }}>sudo chmod 644 /var/www/rede/*.json</code></li>
                        </ol>
                    </div>
                    <button onClick={() => { setLoading(true); fetchData(); }}
                        style={{ padding:'10px 24px', background:'rgba(56,189,248,0.1)', border:'1px solid rgba(56,189,248,0.3)', color:'#38bdf8', borderRadius:8, cursor:'pointer', fontFamily:'Syne, sans-serif', fontWeight:600, fontSize:15 }}>
                        Tentar Novamente
                    </button>
                </div>
            )}
        </div>
    );

    const iface = data.interfaces[0];

    return (
        <div style={{ maxWidth:1400, margin:'0 auto', padding:'24px 20px 100px' }}>
            <Header iface={iface.name} lastUpdate={lastUpdate} autoRefresh={autoRefresh} setAutoRefresh={setAutoRefresh} onRefresh={fetchData} />
            <Tabs tab={tab} setTab={setTab} />
            <div style={{ marginTop:20 }}>
                {tab === 'summary' && <SummaryTab data={iface} />}
                {tab === 'hours'   && <HoursTab   data={iface} />}
                {tab === 'days'    && <DaysTab    data={iface} />}
                {tab === 'months'  && <MonthsTab  data={iface} />}
                {tab === 'years'   && <YearsTab   data={iface} />}
            </div>
            <Footer loadMs={loadMs} iface={iface.name} sys={sysStats} />
        </div>
    );
};

/* ─── Header ─────────────────────────────────────────────── */
const Header = ({ iface, lastUpdate, autoRefresh, setAutoRefresh, onRefresh }) => (
    <header className="panel animate-in" style={{ padding:'20px 24px', marginBottom:16, display:'flex', flexWrap:'wrap', gap:16, justifyContent:'space-between', alignItems:'center' }}>
        <div>
            <a href="/" style={{ textDecoration:'none' }}>
                <h1 style={{ fontFamily:'Syne, sans-serif', fontWeight:800, fontSize:'clamp(22px, 4vw, 30px)', letterSpacing:'-0.04em', color:'#f0f6ff', marginBottom:6 }}>
                    VNSTAT <span className="glow-sky">·</span> <span className="glow-teal">DASHBOARD</span>
                </h1>
            </a>
            <div style={{ display:'flex', alignItems:'center', gap:12, flexWrap:'wrap' }}>
                <div style={{ display:'flex', alignItems:'center', gap:6 }}>
                    <span className="status-dot"><span className="status-dot-inner"></span></span>
                    <span style={{ fontFamily:'JetBrains Mono, monospace', fontSize:13, color:'#4ade80', letterSpacing:'0.05em' }}>ONLINE</span>
                </div>
                <span className="chip chip-sky">
                    <i className="fas fa-ethernet"></i> {iface}
                </span>
                {lastUpdate && (
                    <span style={{ fontFamily:'JetBrains Mono, monospace', fontSize:12, color:'#3d5273', letterSpacing:'0.05em' }}>
                        UPD {lastUpdate.toLocaleTimeString('pt-BR')}
                    </span>
                )}
            </div>
        </div>
        <div style={{ display:'flex', gap:8, alignItems:'center' }}>
            <button onClick={() => setAutoRefresh(v => !v)}
                style={{ padding:'8px 14px', borderRadius:8, cursor:'pointer', fontFamily:'Syne, sans-serif', fontWeight:600, fontSize:13, transition:'all 0.2s',
                    background: autoRefresh ? 'rgba(56,189,248,0.12)' : 'var(--bg-raised)',
                    border: autoRefresh ? '1px solid rgba(56,189,248,0.3)' : '1px solid var(--border-dim)',
                    color: autoRefresh ? '#38bdf8' : '#8ba3cc'
                }}>
                <i className={`fas ${autoRefresh ? 'fa-sync-alt spin' : 'fa-pause'}`} style={{ marginRight:6 }}></i>
                {autoRefresh ? 'AUTO' : 'MANUAL'}
            </button>
            <button onClick={onRefresh}
                style={{ padding:'8px 14px', borderRadius:8, cursor:'pointer', background:'var(--bg-raised)', border:'1px solid var(--border-dim)', color:'#8ba3cc', fontFamily:'Syne, sans-serif', fontWeight:600, fontSize:13, transition:'all 0.2s' }}
                onMouseOver={e => e.currentTarget.style.borderColor='rgba(56,189,248,0.25)'}
                onMouseOut={e => e.currentTarget.style.borderColor='var(--border-dim)'}>
                <i className="fas fa-redo" style={{ marginRight:6 }}></i>REFRESH
            </button>
            <a href="logout.php"
                style={{ padding:'8px 16px', borderRadius:8, textDecoration:'none', background:'rgba(251,113,133,0.07)', border:'1px solid rgba(251,113,133,0.2)', color:'#fb7185', fontFamily:'Syne, sans-serif', fontWeight:600, fontSize:13, transition:'all 0.2s', display:'flex', alignItems:'center', gap:6 }}
                onMouseOver={e => { e.currentTarget.style.background='rgba(251,113,133,0.15)'; e.currentTarget.style.borderColor='rgba(251,113,133,0.4)'; }}
                onMouseOut={e => { e.currentTarget.style.background='rgba(251,113,133,0.07)'; e.currentTarget.style.borderColor='rgba(251,113,133,0.2)'; }}>
                <i className="fas fa-right-from-bracket"></i> SAIR
            </a>
        </div>
    </header>
);

/* ─── Tabs ───────────────────────────────────────────────── */
const TABS = [
    { id:'summary', label:'Sumário',  icon:'fa-chart-pie' },
    { id:'hours',   label:'Horas',    icon:'fa-clock' },
    { id:'days',    label:'Dias',     icon:'fa-calendar-day' },
    { id:'months',  label:'Meses',    icon:'fa-calendar-alt' },
    { id:'years',   label:'Anos',     icon:'fa-calendar' },
];
const Tabs = ({ tab, setTab }) => (
    <nav className="animate-in delay-1" style={{ display:'flex', gap:4, flexWrap:'wrap', padding:'6px', background:'var(--bg-surface)', border:'1px solid var(--border-dim)', borderRadius:12, width:'fit-content' }}>
        {TABS.map(t => (
            <button key={t.id} onClick={() => setTab(t.id)}
                className={tab === t.id ? 'tab-active' : 'tab-inactive'}
                style={{ padding:'9px 20px', borderRadius:8, cursor:'pointer', fontFamily:'Syne, sans-serif', fontWeight:600, fontSize:14, letterSpacing:'0.02em', transition:'all 0.2s', background:'transparent' }}>
                <i className={`fas ${t.icon}`} style={{ marginRight:7 }}></i>{t.label}
            </button>
        ))}
    </nav>
);

/* ─── StatCard ───────────────────────────────────────────── */
const StatCard = ({ title, rx, tx, icon, accent, delay }) => {
    const total = rx + tx;
    const dlPct = total > 0 ? clamp((rx / total) * 100, 2, 98) : 50;
    const ulPct = 100 - dlPct;
    return (
        <div className={`panel card animate-in delay-${delay}`} style={{ padding:20, position:'relative', overflow:'hidden' }}>
            <div style={{ position:'absolute', top:0, left:0, right:0, height:2, background:`linear-gradient(90deg, ${accent}, transparent)` }}></div>
            <div style={{ display:'flex', justifyContent:'space-between', alignItems:'flex-start', marginBottom:16 }}>
                <div>
                    <p style={{ fontFamily:'JetBrains Mono, monospace', fontSize:11, letterSpacing:'0.15em', textTransform:'uppercase', color:'#3d5273', marginBottom:4 }}>{title}</p>
                    <p className="metric-val" style={{ fontSize:'clamp(20px, 2.5vw, 28px)', color:'#f0f6ff' }}>{fmt(total)}</p>
                </div>
                <div style={{ width:36, height:36, borderRadius:10, background:`rgba(${accent === '#4ade80' ? '74,222,128' : accent === '#38bdf8' ? '56,189,248' : accent === '#a78bfa' ? '167,139,250' : '251,191,36'},0.1)`, display:'flex', alignItems:'center', justifyContent:'center' }}>
                    <i className={`fas ${icon}`} style={{ color: accent, fontSize:14 }}></i>
                </div>
            </div>
            <div style={{ display:'flex', flexDirection:'column', gap:8 }}>
                <div>
                    <div style={{ display:'flex', justifyContent:'space-between', marginBottom:5 }}>
                        <span style={{ fontFamily:'JetBrains Mono, monospace', fontSize:12, color:'#3d5273', letterSpacing:'0.05em' }}>↓ DL</span>
                        <span className="metric-val" style={{ fontSize:14, color:'#4ade80' }}>{fmt(rx)}</span>
                    </div>
                    <div className="progress-track" style={{ height:3 }}>
                        <div className="progress-fill-dl" style={{ width:`${dlPct}%`, height:'100%', borderRadius:9999, transition:'width 0.8s ease' }}></div>
                    </div>
                </div>
                <div>
                    <div style={{ display:'flex', justifyContent:'space-between', marginBottom:5 }}>
                        <span style={{ fontFamily:'JetBrains Mono, monospace', fontSize:12, color:'#3d5273', letterSpacing:'0.05em' }}>↑ UL</span>
                        <span className="metric-val" style={{ fontSize:14, color:'#38bdf8' }}>{fmt(tx)}</span>
                    </div>
                    <div className="progress-track" style={{ height:3 }}>
                        <div className="progress-fill-ul" style={{ width:`${ulPct}%`, height:'100%', borderRadius:9999, transition:'width 0.8s ease' }}></div>
                    </div>
                </div>
            </div>
        </div>
    );
};

/* ─── Summary Tab ────────────────────────────────────────── */
const SummaryTab = ({ data }) => {
    const { traffic } = data;

    const currentHour = useMemo(() => {
        if (!traffic.hour?.length) return { rx:0, tx:0 };
        const now = new Date(); const ts = Math.floor(Date.now() / 1000);
        const recent = traffic.hour.slice(-3);
        const match = recent.reduce((best, h) => {
            const d = new Date(h.timestamp * 1000);
            const sameH = d.getHours() === now.getHours();
            const prevH = d.getHours() === (now.getHours() - 1) && now.getMinutes() < 5;
            const sameD = d.toDateString() === now.toDateString();
            if (sameD && (sameH || prevH) && Math.abs(ts - h.timestamp) < Math.abs(ts - (best?.timestamp ?? 0))) return h;
            return best;
        }, null);
        return match ?? traffic.hour.at(-1) ?? { rx:0, tx:0 };
    }, [traffic]);

    const today = useMemo(() => {
        if (!traffic.day?.length) return { rx:0, tx:0 };
        const now = new Date();
        return traffic.day.find(d => {
            const dd = new Date(d.timestamp * 1000);
            return dd.getDate() === now.getDate() && dd.getMonth() === now.getMonth() && dd.getFullYear() === now.getFullYear();
        }) ?? traffic.day.at(-1) ?? { rx:0, tx:0 };
    }, [traffic]);

    const thisMonth = traffic.month?.at(-1) ?? { rx:0, tx:0 };
    const total     = traffic.total;

    const cards = [
        { title:'Esta Hora', rx: currentHour.rx, tx: currentHour.tx, icon:'fa-clock',        accent:'#38bdf8', delay:1 },
        { title:'Hoje',      rx: today.rx,        tx: today.tx,       icon:'fa-calendar-day', accent:'#a78bfa', delay:2 },
        { title:'Este Mês',  rx: thisMonth.rx,    tx: thisMonth.tx,   icon:'fa-calendar-alt', accent:'#4ade80', delay:3 },
        { title:'Total',     rx: total.rx,        tx: total.tx,       icon:'fa-globe',        accent:'#fbbf24', delay:4 },
    ];

    const pieData = [
        { name:'Download', value: total.rx, color:'#4ade80' },
        { name:'Upload',   value: total.tx, color:'#38bdf8' },
    ];

    const top10 = (traffic.top ?? []).slice(0, 10).map(d => ({
        date:  fmtDate(d.timestamp),
        total: d.rx + d.tx,
        rx: d.rx, tx: d.tx
    }));

    const recent12 = (traffic.hour ?? []).slice(-12).map(h => ({
        time: fmtTime(h.timestamp),
        download: h.rx, upload: h.tx
    }));

    return (
        <div style={{ display:'flex', flexDirection:'column', gap:16 }}>
            {/* Stat cards */}
            <div style={{ display:'grid', gridTemplateColumns:'repeat(auto-fit, minmax(220px, 1fr))', gap:12 }}>
                {cards.map((c, i) => <StatCard key={i} {...c} />)}
            </div>

            {/* Pie + Top 10 */}
            <div style={{ display:'grid', gridTemplateColumns:'repeat(auto-fit, minmax(300px, 1fr))', gap:12 }}>
                {/* Pie */}
                <div className="panel animate-in delay-2" style={{ padding:20 }}>
                    <SectionTitle icon="fa-chart-pie" color="#a78bfa" label="Distribuição Total" />
                    <ResponsiveContainer width="100%" height={260}>
                        <PieChart>
                            <Pie data={pieData} cx="50%" cy="50%" innerRadius={60} outerRadius={100}
                                dataKey="value" paddingAngle={3}
                                label={({ name, percent }) => `${name} ${(percent*100).toFixed(1)}%`}
                                labelLine={{ stroke:'rgba(99,140,210,0.3)', strokeWidth:1 }}>
                                {pieData.map((e, i) => <Cell key={i} fill={e.color} />)}
                            </Pie>
                            <Tooltip content={<ChartTooltip />} formatter={v => fmt(v)} />
                        </PieChart>
                    </ResponsiveContainer>
                    <div style={{ display:'flex', justifyContent:'center', gap:24, marginTop:8 }}>
                        {pieData.map((e, i) => (
                            <div key={i} style={{ display:'flex', alignItems:'center', gap:6 }}>
                                <div style={{ width:10, height:10, borderRadius:3, background:e.color }}></div>
                                <span style={{ fontFamily:'JetBrains Mono, monospace', fontSize:13, color:'#8ba3cc' }}>
                                    {e.name}: <span style={{ color: e.color, fontWeight:600 }}>{fmt(e.value)}</span>
                                </span>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Top 10 */}
                <div className="panel animate-in delay-3" style={{ padding:20 }}>
                    <SectionTitle icon="fa-trophy" color="#fbbf24" label="Top 10 Dias" />
                    <div style={{ display:'flex', flexDirection:'column', gap:5, maxHeight:280, overflowY:'auto' }}>
                        {top10.map((d, i) => (
                            <div key={i} className="panel-flat" style={{ padding:'9px 12px', display:'flex', justifyContent:'space-between', alignItems:'center', gap:8 }}>
                                <div style={{ display:'flex', alignItems:'center', gap:8 }}>
                                    <span style={{ fontFamily:'JetBrains Mono, monospace', fontSize:12, color: i === 0 ? '#fbbf24' : '#3d5273', minWidth:24, fontWeight:600 }}>
                                        #{i+1}
                                    </span>
                                    <span style={{ fontSize:14, color:'#8ba3cc' }}>{d.date}</span>
                                </div>
                                <div style={{ display:'flex', gap:12, alignItems:'center' }}>
                                    <span style={{ fontFamily:'JetBrains Mono, monospace', fontSize:12, color:'#4ade80' }}>↓{fmt(d.rx)}</span>
                                    <span style={{ fontFamily:'JetBrains Mono, monospace', fontSize:12, color:'#38bdf8' }}>↑{fmt(d.tx)}</span>
                                    <span className="metric-val" style={{ fontSize:14, color:'#f0f6ff', minWidth:70, textAlign:'right' }}>{fmt(d.total)}</span>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>

            {/* Area chart */}
            <div className="panel animate-in delay-4" style={{ padding:20 }}>
                <SectionTitle icon="fa-chart-area" color="#38bdf8" label="Atividade — Últimas 12 Horas" />
                <ResponsiveContainer width="100%" height={280}>
                    <AreaChart data={recent12}>
                        <defs>
                            <linearGradient id="gDl" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="5%" stopColor="#4ade80" stopOpacity={0.3} />
                                <stop offset="95%" stopColor="#4ade80" stopOpacity={0} />
                            </linearGradient>
                            <linearGradient id="gUl" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="5%" stopColor="#38bdf8" stopOpacity={0.25} />
                                <stop offset="95%" stopColor="#38bdf8" stopOpacity={0} />
                            </linearGradient>
                        </defs>
                        <CartesianGrid {...CHART_GRID} />
                        <XAxis dataKey="time" {...AXIS_STYLE} />
                        <YAxis {...AXIS_STYLE} tickFormatter={fmt} width={72} />
                        <Tooltip content={<ChartTooltip />} />
                        <Legend iconType="circle" iconSize={8} />
                        <Area type="monotone" dataKey="download" stroke="#4ade80" strokeWidth={2} fill="url(#gDl)" name="Download" dot={false} activeDot={{ r:4, fill:'#4ade80' }} />
                        <Area type="monotone" dataKey="upload"   stroke="#38bdf8" strokeWidth={2} fill="url(#gUl)" name="Upload"   dot={false} activeDot={{ r:4, fill:'#38bdf8' }} />
                    </AreaChart>
                </ResponsiveContainer>
            </div>
        </div>
    );
};

/* ─── Hours Tab ──────────────────────────────────────────── */
const HoursTab = ({ data }) => {
    const hours = (data.traffic.hour ?? []).slice(-24).map(h => ({
        time: fmtTime(h.timestamp),
        fullDate: new Date(h.timestamp * 1000).toLocaleString('pt-BR'),
        download: h.rx, upload: h.tx, total: h.rx + h.tx
    }));

    return (
        <div style={{ display:'flex', flexDirection:'column', gap:12 }}>
            <div className="panel animate-in" style={{ padding:20 }}>
                <SectionTitle icon="fa-clock" color="#38bdf8" label="Tráfego — Últimas 24 Horas" />
                <ResponsiveContainer width="100%" height={360}>
                    <BarChart data={hours} barGap={2}>
                        <CartesianGrid {...CHART_GRID} />
                        <XAxis dataKey="time" {...AXIS_STYLE} />
                        <YAxis {...AXIS_STYLE} tickFormatter={fmt} width={72} />
                        <Tooltip content={<ChartTooltip />} />
                        <Legend iconType="circle" iconSize={8} />
                        <Bar dataKey="download" fill="#4ade80" name="Download" radius={[3,3,0,0]} />
                        <Bar dataKey="upload"   fill="#38bdf8" name="Upload"   radius={[3,3,0,0]} />
                    </BarChart>
                </ResponsiveContainer>
            </div>
            <DataTable columns={['Horário','Download','Upload','Total']} rows={[...hours].reverse().map(h => [
                h.fullDate,
                <span className="metric-val" style={{ color:'#4ade80', fontSize:14 }}>{fmt(h.download)}</span>,
                <span className="metric-val" style={{ color:'#38bdf8', fontSize:14 }}>{fmt(h.upload)}</span>,
                <span className="metric-val" style={{ color:'#f0f6ff', fontSize:14 }}>{fmt(h.total)}</span>,
            ])} />
        </div>
    );
};

/* ─── Days Tab ───────────────────────────────────────────── */
const DaysTab = ({ data }) => {
    const days = (data.traffic.day ?? []).slice(-30).map(d => ({
        date: fmtDate(d.timestamp),
        download: d.rx, upload: d.tx, total: d.rx + d.tx
    }));

    return (
        <div style={{ display:'flex', flexDirection:'column', gap:12 }}>
            <div className="panel animate-in" style={{ padding:20 }}>
                <SectionTitle icon="fa-calendar-day" color="#a78bfa" label="Tráfego — Últimos 30 Dias" />
                <ResponsiveContainer width="100%" height={360}>
                    <LineChart data={days}>
                        <CartesianGrid {...CHART_GRID} />
                        <XAxis dataKey="date" {...AXIS_STYLE} angle={-40} textAnchor="end" height={70} />
                        <YAxis {...AXIS_STYLE} tickFormatter={fmt} width={72} />
                        <Tooltip content={<ChartTooltip />} />
                        <Legend iconType="circle" iconSize={8} />
                        <Line type="monotone" dataKey="download" stroke="#4ade80" strokeWidth={2} dot={false} activeDot={{ r:4 }} name="Download" />
                        <Line type="monotone" dataKey="upload"   stroke="#38bdf8" strokeWidth={2} dot={false} activeDot={{ r:4 }} name="Upload" />
                    </LineChart>
                </ResponsiveContainer>
            </div>
            <DataTable columns={['Data','Download','Upload','Total']} rows={[...days].reverse().map(d => [
                d.date,
                <span className="metric-val" style={{ color:'#4ade80', fontSize:14 }}>{fmt(d.download)}</span>,
                <span className="metric-val" style={{ color:'#38bdf8', fontSize:14 }}>{fmt(d.upload)}</span>,
                <span className="metric-val" style={{ color:'#f0f6ff', fontSize:14 }}>{fmt(d.total)}</span>,
            ])} />
        </div>
    );
};

/* ─── Months Tab ─────────────────────────────────────────── */
const MonthsTab = ({ data }) => {
    const months = (data.traffic.month ?? []).map(m => ({
        month: new Date(m.timestamp * 1000).toLocaleDateString('pt-BR', { year:'numeric', month:'short' }),
        download: m.rx, upload: m.tx, total: m.rx + m.tx
    }));

    const accentColors = ['#38bdf8','#4ade80','#a78bfa','#fbbf24','#fb7185','#2dd4bf'];

    return (
        <div style={{ display:'flex', flexDirection:'column', gap:12 }}>
            <div className="panel animate-in" style={{ padding:20 }}>
                <SectionTitle icon="fa-calendar-alt" color="#fbbf24" label="Tráfego Mensal" />
                <ResponsiveContainer width="100%" height={360}>
                    <BarChart data={months} barGap={3}>
                        <CartesianGrid {...CHART_GRID} />
                        <XAxis dataKey="month" {...AXIS_STYLE} />
                        <YAxis {...AXIS_STYLE} tickFormatter={fmt} width={72} />
                        <Tooltip content={<ChartTooltip />} />
                        <Legend iconType="circle" iconSize={8} />
                        <Bar dataKey="download" fill="#4ade80" name="Download" radius={[3,3,0,0]} />
                        <Bar dataKey="upload"   fill="#38bdf8" name="Upload"   radius={[3,3,0,0]} />
                    </BarChart>
                </ResponsiveContainer>
            </div>
            <div style={{ display:'grid', gridTemplateColumns:'repeat(auto-fill, minmax(200px, 1fr))', gap:10 }}>
                {[...months].reverse().map((m, i) => (
                    <div key={i} className="panel animate-in" style={{ padding:0, overflow:'hidden' }}>
                        <div style={{ height:3, background: accentColors[i % accentColors.length] }}></div>
                        <div style={{ padding:'14px 16px' }}>
                            <p style={{ fontFamily:'Syne, sans-serif', fontWeight:700, fontSize:13, color:'#f0f6ff', marginBottom:12, textTransform:'capitalize' }}>{m.month}</p>
                            <div style={{ display:'flex', flexDirection:'column', gap:6 }}>
                                <MonthRow label="↓ DL" val={m.download} col="#4ade80" />
                                <MonthRow label="↑ UL" val={m.upload}   col="#38bdf8" />
                                <div style={{ borderTop:'1px solid var(--border-dim)', paddingTop:8, marginTop:2 }}>
                                    <MonthRow label="Total" val={m.total} col="#f0f6ff" bold />
                                </div>
                            </div>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
};

const MonthRow = ({ label, val, col, bold }) => (
    <div style={{ display:'flex', justifyContent:'space-between', alignItems:'center' }}>
        <span style={{ fontFamily:'JetBrains Mono, monospace', fontSize:12, color:'#3d5273', letterSpacing:'0.08em' }}>{label}</span>
        <span className="metric-val" style={{ fontSize: bold ? 16 : 14, color: col, fontWeight: bold ? 700 : 600 }}>{fmt(val)}</span>
    </div>
);

/* ─── Years Tab ──────────────────────────────────────────── */
const YearsTab = ({ data }) => {
    const years = (data.traffic.year ?? []).map(y => ({
        year: y.date.year, download: y.rx, upload: y.tx, total: y.rx + y.tx
    }));

    if (!years.length) return (
        <div className="panel" style={{ padding:48, textAlign:'center' }}>
            <i className="fas fa-calendar" style={{ fontSize:40, color:'#3d5273', marginBottom:16 }}></i>
            <p style={{ color:'#3d5273', fontFamily:'JetBrains Mono, monospace', fontSize:14 }}>NENHUM DADO ANUAL DISPONÍVEL</p>
        </div>
    );

    const avgTotal = years.reduce((s, y) => s + y.total, 0) / years.length;
    const maxY = years.reduce((m, y) => y.total > m.total ? y : m, years[0]);
    const curYear = new Date().getFullYear();

    return (
        <div style={{ display:'flex', flexDirection:'column', gap:12 }}>
            <div style={{ display:'grid', gridTemplateColumns:'repeat(auto-fit, minmax(160px, 1fr))', gap:10 }}>
                {[
                    { label:'Total de Anos', val: years.length,       icon:'fa-calendar-check', accent:'#38bdf8', mono:false },
                    { label:'Média Anual',   val: fmt(avgTotal),      icon:'fa-balance-scale',  accent:'#a78bfa', mono:true  },
                    { label:'Ano Recorde',   val: `${maxY.year}`,     icon:'fa-trophy',         accent:'#fbbf24', mono:false },
                ].map((s, i) => (
                    <div key={i} className="panel animate-in" style={{ padding:16, position:'relative', overflow:'hidden' }}>
                        <div style={{ position:'absolute', top:0, left:0, right:0, height:2, background: s.accent }}></div>
                        <p style={{ fontFamily:'JetBrains Mono, monospace', fontSize:11, letterSpacing:'0.15em', textTransform:'uppercase', color:'#3d5273', marginBottom:8 }}>{s.label}</p>
                        <p style={{ fontFamily: s.mono ? 'JetBrains Mono, monospace' : 'Syne, sans-serif', fontWeight:700, fontSize:26, color: s.accent }}>{s.val}</p>
                        {s.label === 'Ano Recorde' && <p style={{ fontFamily:'JetBrains Mono, monospace', fontSize:13, color:'#8ba3cc', marginTop:2 }}>{fmt(maxY.total)}</p>}
                    </div>
                ))}
            </div>

            <div className="panel animate-in delay-1" style={{ padding:20 }}>
                <SectionTitle icon="fa-chart-bar" color="#38bdf8" label="Tráfego Anual Comparativo" />
                <ResponsiveContainer width="100%" height={360}>
                    <BarChart data={years} barGap={4}>
                        <CartesianGrid {...CHART_GRID} />
                        <XAxis dataKey="year" {...AXIS_STYLE} />
                        <YAxis {...AXIS_STYLE} tickFormatter={fmt} width={72} />
                        <Tooltip content={<ChartTooltip />} />
                        <ReferenceLine y={avgTotal} stroke="rgba(167,139,250,0.4)" strokeDasharray="6 3" label={{ value:'Média', fill:'#a78bfa', fontSize:10, fontFamily:'JetBrains Mono, monospace' }} />
                        <Legend iconType="circle" iconSize={8} />
                        <Bar dataKey="download" fill="#4ade80" name="Download" radius={[3,3,0,0]} />
                        <Bar dataKey="upload"   fill="#38bdf8" name="Upload"   radius={[3,3,0,0]} />
                    </BarChart>
                </ResponsiveContainer>
            </div>

            <div style={{ display:'grid', gridTemplateColumns:'repeat(auto-fill, minmax(220px, 1fr))', gap:10 }}>
                {[...years].reverse().map((y, i) => {
                    const pct = clamp((y.total / maxY.total) * 100, 2, 100);
                    const vsAvg = ((y.total / avgTotal - 1) * 100).toFixed(1);
                    return (
                        <div key={i} className="panel animate-in" style={{ padding:18, position:'relative', overflow:'hidden' }}>
                            {y.year === curYear && (
                                <span style={{ position:'absolute', top:10, right:10, fontFamily:'JetBrains Mono, monospace', fontSize:11, padding:'2px 8px', borderRadius:4, background:'rgba(74,222,128,0.1)', border:'1px solid rgba(74,222,128,0.25)', color:'#4ade80', letterSpacing:'0.08em' }}>ATUAL</span>
                            )}
                            <p style={{ fontFamily:'Syne, sans-serif', fontWeight:800, fontSize:30, color:'#f0f6ff', marginBottom:14, letterSpacing:'-0.03em' }}>{y.year}</p>
                            <div style={{ display:'flex', flexDirection:'column', gap:7 }}>
                                <MonthRow label="↓ DL" val={y.download} col="#4ade80" />
                                <MonthRow label="↑ UL" val={y.upload}   col="#38bdf8" />
                                <div style={{ borderTop:'1px solid var(--border-dim)', paddingTop:8, marginTop:2 }}>
                                    <MonthRow label="Total" val={y.total} col="#f0f6ff" bold />
                                </div>
                            </div>
                            <div style={{ marginTop:12 }}>
                                <div style={{ display:'flex', justifyContent:'space-between', marginBottom:5 }}>
                                    <span style={{ fontFamily:'JetBrains Mono, monospace', fontSize:11, color:'#3d5273', letterSpacing:'0.08em' }}>vs MÉDIA</span>
                                    <span style={{ fontFamily:'JetBrains Mono, monospace', fontSize:11, color: parseFloat(vsAvg) >= 0 ? '#4ade80' : '#fb7185' }}>
                                        {parseFloat(vsAvg) >= 0 ? '+' : ''}{vsAvg}%
                                    </span>
                                </div>
                                <div className="progress-track" style={{ height:3 }}>
                                    <div style={{ width:`${pct}%`, height:'100%', borderRadius:9999, background:`linear-gradient(90deg, ${y.total >= avgTotal ? '#4ade80' : '#38bdf8'}, transparent)`, transition:'width 1s ease' }}></div>
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
};

/* ─── Shared: SectionTitle ───────────────────────────────── */
const SectionTitle = ({ icon, color, label }) => (
    <div style={{ display:'flex', alignItems:'center', gap:8, marginBottom:16 }}>
        <div style={{ width:28, height:28, borderRadius:8, background:`rgba(${color === '#38bdf8' ? '56,189,248' : color === '#4ade80' ? '74,222,128' : color === '#a78bfa' ? '167,139,250' : color === '#fbbf24' ? '251,191,36' : '251,113,133'},0.1)`, display:'flex', alignItems:'center', justifyContent:'center', flexShrink:0 }}>
            <i className={`fas ${icon}`} style={{ color, fontSize:12 }}></i>
        </div>
        <h3 style={{ fontFamily:'Syne, sans-serif', fontWeight:700, fontSize:16, color:'#f0f6ff', letterSpacing:'-0.01em' }}>{label}</h3>
    </div>
);

/* ─── Shared: DataTable ──────────────────────────────────── */
const DataTable = ({ columns, rows }) => (
    <div className="panel animate-in delay-2" style={{ padding:20 }}>
        <SectionTitle icon="fa-table" color="#a78bfa" label="Dados Detalhados" />
        <div style={{ overflowX:'auto' }}>
            <table className="data-table">
                <thead>
                    <tr>{columns.map((c, i) => <th key={i} style={{ textAlign: i === 0 ? 'left' : 'right' }}>{c}</th>)}</tr>
                </thead>
                <tbody>
                    {rows.map((row, ri) => (
                        <tr key={ri}>
                            {row.map((cell, ci) => (
                                <td key={ci} style={{ color:'#8ba3cc', textAlign: ci === 0 ? 'left' : 'right', fontFamily: ci === 0 ? 'inherit' : 'JetBrains Mono, monospace' }}>
                                    {cell}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    </div>
);

/* ─── Footer ─────────────────────────────────────────────── */
const Footer = ({ loadMs, iface, sys }) => {
    const [now, setNow] = useState(new Date());
    useEffect(() => {
        const id = setInterval(() => setNow(new Date()), 1000);
        return () => clearInterval(id);
    }, []);
    const memPct = sys.memory?.total > 0 ? ((sys.memory.used / sys.memory.total) * 100).toFixed(0) : null;
    return (
        <footer className="footer-bar" style={{ position:'fixed', bottom:0, left:0, right:0, padding:'10px 20px', zIndex:100 }}>
            <div style={{ maxWidth:1400, margin:'0 auto', display:'flex', flexWrap:'wrap', gap:10, justifyContent:'space-between', alignItems:'center' }}>
                <div style={{ display:'flex', gap:12, alignItems:'center', flexWrap:'wrap' }}>
                    <span style={{ fontFamily:'Syne, sans-serif', fontWeight:700, fontSize:13, color:'#8ba3cc', letterSpacing:'0.05em' }}>VNSTAT</span>
                    <span className="chip chip-sky"><i className="fas fa-ethernet"></i>{iface}</span>
                    {sys.dbSize > 0 && <span className="chip chip-violet"><i className="fas fa-database"></i>{fmt(sys.dbSize)}</span>}
                    {sys.uptime > 0 && <span className="chip chip-green"><i className="fas fa-power-off"></i>{fmtUptime(sys.uptime)}</span>}
                    {memPct && <span className="chip chip-rose"><i className="fas fa-memory"></i>RAM {memPct}%</span>}
                </div>
                <div style={{ display:'flex', gap:12, alignItems:'center', flexWrap:'wrap' }}>
                    <span style={{ fontFamily:'JetBrains Mono, monospace', fontSize:12, color:'#3d5273', letterSpacing:'0.08em' }}>
                        {now.toLocaleDateString('pt-BR')} <span style={{ color:'#8ba3cc' }}>{now.toLocaleTimeString('pt-BR')}</span>
                    </span>
                    {loadMs > 0 && <span className="chip chip-amber"><i className="fas fa-bolt"></i>{loadMs}ms</span>}
                    <span style={{ fontFamily:'JetBrains Mono, monospace', fontSize:11, color:'#3d5273', letterSpacing:'0.05em' }}>React · Recharts</span>
                </div>
            </div>
        </footer>
    );
};

/* ─── Mount ──────────────────────────────────────────────── */
ReactDOM.createRoot(document.getElementById('root')).render(<App />);
</script>
</body>
</html>
