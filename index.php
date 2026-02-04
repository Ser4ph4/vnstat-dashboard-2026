<?php
require_once 'config.php';
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>VnStat - Dashboard</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="libs/fontawesome/css/all.min.css">
    <!-- Tailwind CSS -->
    <script src="libs/tailwind.min.js"></script>
    <!-- React e bibliotecas relacionadas -->
    <script src="libs/react.production.min.js"></script>
    <script src="libs/react-dom.production.min.js"></script>
    <script src="libs/babel.min.js"></script>
    <script src="libs/prop-types.min.js"></script>
    <script src="libs/Recharts.js"></script>
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

    * {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    body {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        min-height: 100vh;
    }

    .glass-effect {
        background: rgba(15, 23, 42, 0.7);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(148, 163, 184, 0.1);
    }

    .card-hover {
        transition: all 0.3s ease;
    }

    .card-hover:hover {
        transform: translateY(-2px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
    }

    .gradient-text {
        background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .stat-card {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
    }

    @keyframes pulse-slow {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.7;
        }
    }

    .online-indicator {
        animation: pulse-slow 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }

    .scrollbar-hide {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    .footer {
        background: rgba(15, 23, 42, 0.95);
        backdrop-filter: blur(20px);
        border-top: 1px solid rgba(148, 163, 184, 0.1);
    }

    .pulse-icon {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }
    }
    </style>
</head>

<body class="bg-slate-900">
    <div id="root"></div>

    <script type="text/babel">
        const { useState, useEffect, useMemo } = React;
        const { 
            LineChart, Line, BarChart, Bar, AreaChart, Area,
            XAxis, YAxis, CartesianGrid, Tooltip, Legend, 
            ResponsiveContainer, PieChart, Pie, Cell 
        } = Recharts;

        // Utility functions
        const formatBytes = (bytes, decimals = 2) => {
            if (!bytes || bytes === 0 || isNaN(bytes)) return '0 B';
            
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            const index = Math.min(i, sizes.length - 1);
            const value = parseFloat((bytes / Math.pow(k, index)).toFixed(dm));
            
            return value + ' ' + sizes[index];
        };

        const formatDate = (timestamp) => {
            const date = new Date(timestamp * 1000);
            return date.toLocaleDateString('pt-BR');
        };

        const formatTime = (timestamp) => {
            const date = new Date(timestamp * 1000);
            return date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        };

        const formatUptime = (seconds) => {
            if (!seconds) return '0d 0h';
            const days = Math.floor(seconds / 86400);
            const hours = Math.floor((seconds % 86400) / 3600);
            const mins = Math.floor((seconds % 3600) / 60);
            return `${days}d ${hours}h ${mins}m`;
        };

        // Main App Component
        const VnStatDashboard = () => {
            const [data, setData] = useState(null);
            const [loading, setLoading] = useState(true);
            const [activeTab, setActiveTab] = useState('summary');
            const [lastUpdate, setLastUpdate] = useState(null);
            const [error, setError] = useState(null);
            const [autoRefresh, setAutoRefresh] = useState(true);
            const [loadTime, setLoadTime] = useState(0);
            const [pageLoadTime, setPageLoadTime] = useState(0);
            const [systemStats, setSystemStats] = useState({
                dbSize: 0,
                uptime: 0,
                memory: { total: 0, used: 0, available: 0 }
            });

            const loadData = async () => {
                const startTime = performance.now();
                console.log('🔄 Iniciando carregamento de dados...', new Date().toLocaleTimeString());
                
                try {
                    setError(null);
                    
                    const possibleFiles = [
                        'vnstat_dump_eth0.json',
                        'vnstat_dump_wlx503eaa8913bf.json',
                        'vnstat_dump_wlan0.json',
                        'vnstat_dump_enp0s3.json',
                        'vnstat_dump_eno1.json',
                        'vnstat_dump_wlan1.json'
                    ];
                    
                    for (const file of possibleFiles) {
                        try {
                            const cacheBuster = new Date().getTime();
                            const response = await fetch(`${file}?_=${cacheBuster}`, {
                                cache: 'no-store',
                                headers: {
                                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                                    'Pragma': 'no-cache',
                                    'Expires': '0'
                                }
                            });
                            
                            if (response.ok) {
                                const jsonData = await response.json();
                                setData(jsonData);
                                setLastUpdate(new Date());
                                setLoading(false);
                                
                                const endTime = performance.now();
                                const loadTimeMs = Math.round(endTime - startTime);
                                setLoadTime(loadTimeMs);
                                
                                console.log('✅ Dados carregados com sucesso!');
                                console.log('📊 Estatísticas:');
                                console.log('  - Arquivo:', file);
                                console.log('  - Interface:', jsonData.interfaces[0].name);
                                console.log('  - Total RX:', formatBytes(jsonData.interfaces[0].traffic.total.rx));
                                console.log('  - Total TX:', formatBytes(jsonData.interfaces[0].traffic.total.tx));
                                console.log('  - Horas disponíveis:', jsonData.interfaces[0].traffic.hour.length);
                                console.log('  - Dias disponíveis:', jsonData.interfaces[0].traffic.day.length);
                                console.log('  - Tempo de carregamento:', loadTimeMs, 'ms');
                                
                                // Carregar estatísticas do sistema
                                try {
                                    const statsResponse = await fetch('api_stats.php', { cache: 'no-store' });
                                    if (statsResponse.ok) {
                                        const statsData = await statsResponse.json();
                                        if (statsData.success) {
                                            setSystemStats({
                                                dbSize: statsData.data.database.size,
                                                uptime: statsData.data.system.uptime,
                                                memory: statsData.data.system.memory
                                            });
                                            console.log('✓ Estatísticas do sistema carregadas');
                                        }
                                    }
                                } catch (e) {
                                    console.warn('⚠️  Estatísticas do sistema indisponíveis');
                                }
                                
                                return;
                            }
                        } catch (e) {
                            continue;
                        }
                    }
                    
                    throw new Error('Nenhum arquivo JSON do vnstat encontrado.');
                    
                } catch (error) {
                    console.error('✗ Erro ao carregar dados:', error);
                    setError(error.message);
                    setLoading(false);
                }
            };

            useEffect(() => {
                const pageStart = performance.timing.navigationStart;
                const pageEnd = performance.timing.loadEventEnd;
                if (pageEnd > 0) {
                    setPageLoadTime(pageEnd - pageStart);
                }
                
                loadData();
                
                if (autoRefresh) {
                    const interval = setInterval(loadData, 5 * 60 * 1000);
                    return () => clearInterval(interval);
                }
            }, [autoRefresh]);

            if (loading || !data) {
                return (
                    <div className="min-h-screen flex items-center justify-center">
                        <div className="text-center max-w-2xl mx-auto p-8">
                            {!error ? (
                                <>
                                    <div className="inline-block animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-blue-500 mb-4"></div>
                                    <p className="text-slate-300 text-lg">
                                        <i className="fas fa-spinner fa-spin mr-2"></i>
                                        Carregando dados...
                                    </p>
                                </>
                            ) : (
                                <div className="glass-effect rounded-2xl p-8">
                                    <div className="text-6xl mb-4">⚠️</div>
                                    <h2 className="text-2xl font-bold text-red-400 mb-4">
                                        <i className="fas fa-exclamation-triangle mr-2"></i>
                                        Erro ao Carregar Dados
                                    </h2>
                                    <p className="text-slate-300 mb-6">{error}</p>
                                    <div className="bg-slate-800 rounded-lg p-4 text-left mb-6">
                                        <p className="text-sm text-slate-400 mb-2">Passos para resolver:</p>
                                        <ol className="text-sm text-slate-300 space-y-2 list-decimal list-inside">
                                            <li>Verifique se o <code className="bg-slate-700 px-2 py-1 rounded">update_data.php</code> foi executado</li>
                                            <li>Execute: <code className="bg-slate-700 px-2 py-1 rounded">sudo php /var/www/rede/update_data.php</code></li>
                                            <li>Verifique se o arquivo JSON existe: <code className="bg-slate-700 px-2 py-1 rounded">ls -la /var/www/rede/*.json</code></li>
                                            <li>Verifique as permissões: <code className="bg-slate-700 px-2 py-1 rounded">sudo chmod 644 /var/www/rede/*.json</code></li>
                                            <li>Abra o Console do navegador (F12) para mais detalhes</li>
                                        </ol>
                                    </div>
                                    <button
                                        onClick={() => { setLoading(true); setError(null); loadData(); }}
                                        className="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-all"
                                    >
                                        <i className="fas fa-redo mr-2"></i>
                                        Tentar Novamente
                                    </button>
                                </div>
                            )}
                        </div>
                    </div>
                );
            }

            const interfaceData = data.interfaces[0];

            return (
                <div className="min-h-screen p-4 md:p-8 pb-24">
                    <Header 
                        interfaceName={interfaceData.name}
                        lastUpdate={lastUpdate}
                        autoRefresh={autoRefresh}
                        setAutoRefresh={setAutoRefresh}
                        onRefresh={loadData}
                    />
                    
                    <TabNavigation activeTab={activeTab} setActiveTab={setActiveTab} />
                    
                    <div className="mt-6">
                        {activeTab === 'summary' && <SummaryTab data={interfaceData} />}
                        {activeTab === 'hours' && <HoursTab data={interfaceData} />}
                        {activeTab === 'days' && <DaysTab data={interfaceData} />}
                        {activeTab === 'months' && <MonthsTab data={interfaceData} />}
                        {activeTab === 'years' && <YearsTab data={interfaceData} />}
                    </div>
                    
                    <Footer 
                        loadTime={loadTime}
                        pageLoadTime={pageLoadTime}
                        interfaceName={interfaceData.name}
                        systemStats={systemStats}
                    />
                </div>
            );
        };

        // Header Component
        const Header = ({ interfaceName, lastUpdate, autoRefresh, setAutoRefresh, onRefresh }) => (
            <div className="glass-effect rounded-2xl p-6 mb-6 card-hover">
                <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h1 className="text-4xl font-bold text-white mb-2">
                            <i className="fas fa-chart-line mr-3"></i>
                            VnStat <span className="gradient-text">Tráfego</span>
                        </h1>
                        <div className="flex items-center gap-3 text-slate-400">
                            <div className="flex items-center gap-2">
                                <div className="w-2 h-2 bg-green-500 rounded-full online-indicator"></div>
                                <span className="text-sm font-medium">
                                    <i className="fas fa-plug mr-1"></i>Online
                                </span>
                            </div>
                            <span className="text-slate-600">•</span>
                            <span className="text-sm">
                                <i className="fas fa-network-wired mr-1"></i>
                                Interface: <span className="text-blue-400 font-mono">{interfaceName}</span>
                            </span>
                        </div>
                    </div>
                    
                    <div className="flex flex-col md:flex-row items-start md:items-center gap-3">
                        <div className="text-sm text-slate-400">
                            {lastUpdate && (
                                <span>
                                    <i className="far fa-clock mr-1"></i>
                                    Atualizado: {lastUpdate.toLocaleTimeString('pt-BR')}
                                </span>
                            )}
                        </div>
                        
                        <div className="flex gap-2">
                            <button
                                onClick={() => setAutoRefresh(!autoRefresh)}
                                className={`px-4 py-2 rounded-lg text-sm font-medium transition-all ${
                                    autoRefresh 
                                        ? 'bg-blue-600 text-white hover:bg-blue-700' 
                                        : 'bg-slate-700 text-slate-300 hover:bg-slate-600'
                                }`}
                            >
                                <i className={`fas ${autoRefresh ? 'fa-sync-alt fa-spin' : 'fa-pause'} mr-1`}></i>
                                {autoRefresh ? 'Auto' : 'Manual'}
                            </button>
                            
                            <button
                                onClick={onRefresh}
                                className="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg text-sm font-medium transition-all"
                            >
                                <i className="fas fa-redo mr-1"></i> Atualizar
                            </button>
                            <a href="logout.php" className="hidden md:flex px-6 py-3 bg-red-600/10 hover:bg-red-600 text-red-500 hover:text-white border border-red-500/20 rounded-2xl text-xs font-bold transition-all uppercase tracking-widest items-center gap-2">
            <i className="fa-solid fa-right-from-bracket"></i> Sair
        </a>
                        </div>
                    </div>
                </div>
            </div>
        );

        // Tab Navigation Component
        const TabNavigation = ({ activeTab, setActiveTab }) => {
            const tabs = [
                { id: 'summary', label: 'Sumário', icon: 'fa-chart-pie' },
                { id: 'hours', label: 'Horas', icon: 'fa-clock' },
                { id: 'days', label: 'Dias', icon: 'fa-calendar-day' },
                { id: 'months', label: 'Meses', icon: 'fa-calendar-alt' },
                { id: 'years', label: 'Anos', icon: 'fa-calendar' }
            ];

            return (
                <div className="glass-effect rounded-2xl p-2 inline-flex gap-2 flex-wrap">
                    {tabs.map(tab => (
                        <button
                            key={tab.id}
                            onClick={() => setActiveTab(tab.id)}
                            className={`px-6 py-3 rounded-xl font-medium transition-all ${
                                activeTab === tab.id
                                    ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/50'
                                    : 'text-slate-400 hover:text-white hover:bg-slate-700/50'
                            }`}
                        >
                            <i className={`fas ${tab.icon} mr-2`}></i>
                            {tab.label}
                        </button>
                    ))}
                </div>
            );
        };

        // Summary Tab Component
        const SummaryTab = ({ data }) => {
            // CORRIGIDO: Busca inteligente pela hora atual
            const currentHourData = useMemo(() => {
                if (!data || !data.traffic || !data.traffic.hour || data.traffic.hour.length === 0) {
                    return { rx: 0, tx: 0 };
                }
                
                const now = new Date();
                const currentTimestamp = Math.floor(Date.now() / 1000);
                
                const recentHours = data.traffic.hour.slice(-3);
                
                let bestMatch = null;
                let smallestDiff = Infinity;
                
                for (const hour of recentHours) {
                    const hourDate = new Date(hour.timestamp * 1000);
                    const diff = Math.abs(currentTimestamp - hour.timestamp);
                    
                    const sameHour = hourDate.getHours() === now.getHours();
                    const previousHour = hourDate.getHours() === (now.getHours() - 1) && now.getMinutes() < 5;
                    const sameDay = hourDate.getDate() === now.getDate() && 
                                   hourDate.getMonth() === now.getMonth() &&
                                   hourDate.getFullYear() === now.getFullYear();
                    
                    if (sameDay && (sameHour || previousHour) && diff < smallestDiff) {
                        smallestDiff = diff;
                        bestMatch = hour;
                    }
                }
                
                if (!bestMatch) {
                    bestMatch = data.traffic.hour[data.traffic.hour.length - 1];
                    console.log('⚠️  Usando última hora disponível:', new Date(bestMatch.timestamp * 1000).toLocaleString());
                } else {
                    console.log('✓ Hora atual encontrada:', new Date(bestMatch.timestamp * 1000).toLocaleString());
                }
                
                return bestMatch || { rx: 0, tx: 0 };
            }, [data]);

            // CORRIGIDO: Busca específica pelo dia de hoje
            const todayData = useMemo(() => {
                if (!data || !data.traffic || !data.traffic.day || data.traffic.day.length === 0) {
                    return { rx: 0, tx: 0 };
                }
                
                const now = new Date();
                const today = now.getDate();
                const month = now.getMonth() + 1;
                const year = now.getFullYear();
                
                const todayRecord = data.traffic.day.find(d => {
                    const dayDate = new Date(d.timestamp * 1000);
                    return dayDate.getDate() === today &&
                           dayDate.getMonth() + 1 === month &&
                           dayDate.getFullYear() === year;
                });
                
                const result = todayRecord || data.traffic.day[data.traffic.day.length - 1] || { rx: 0, tx: 0 };
                
                console.log('✓ Dados de hoje:', new Date(result.timestamp * 1000).toLocaleDateString(), 
                            'RX:', formatBytes(result.rx), 'TX:', formatBytes(result.tx));
                
                return result;
            }, [data]);

            const thisMonthData = useMemo(() => {
                const month = data.traffic.month[data.traffic.month.length - 1] || { rx: 0, tx: 0 };
                return month;
            }, [data]);

            const totalData = data.traffic.total;

            const summaryCards = [
                {
                    title: 'Nesta Hora',
                    download: currentHourData.rx,
                    upload: currentHourData.tx,
                    icon: 'fa-clock',
                    iconColor: 'text-blue-400',
                    color: 'from-blue-500 to-cyan-500'
                },
                {
                    title: 'Hoje',
                    download: todayData.rx,
                    upload: todayData.tx,
                    icon: 'fa-calendar-day',
                    iconColor: 'text-purple-400',
                    color: 'from-purple-500 to-pink-500'
                },
                {
                    title: 'Este Mês',
                    download: thisMonthData.rx,
                    upload: thisMonthData.tx,
                    icon: 'fa-calendar-alt',
                    iconColor: 'text-green-400',
                    color: 'from-green-500 to-emerald-500'
                },
                {
                    title: 'Total Geral',
                    download: totalData.rx,
                    upload: totalData.tx,
                    icon: 'fa-globe',
                    iconColor: 'text-orange-400',
                    color: 'from-orange-500 to-red-500'
                }
            ];

            return (
                <div className="space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        {summaryCards.map((card, index) => (
                            <SummaryCard key={index} {...card} />
                        ))}
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <TrafficDistribution data={data} />
                        <Top10Days data={data} />
                    </div>

                    <RecentActivity data={data} />
                </div>
            );
        };

        // Summary Card Component
        const SummaryCard = ({ title, download, upload, icon, iconColor, color }) => {
            const total = download + upload;
            const downloadPercent = total > 0 ? (download / total) * 100 : 50;
            const uploadPercent = total > 0 ? (upload / total) * 100 : 50;

            return (
                <div className="glass-effect rounded-2xl p-6 card-hover">
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="text-slate-300 font-medium">{title}</h3>
                        <i className={`fas ${icon} text-3xl ${iconColor}`}></i>
                    </div>
                    
                    <div className="space-y-3">
                        <div>
                            <div className="flex justify-between items-center mb-1">
                                <span className="text-xs text-slate-400">
                                    <i className="fas fa-arrow-down mr-1"></i>Download
                                </span>
                                <span className="text-sm font-bold text-green-400">{formatBytes(download)}</span>
                            </div>
                            <div className="w-full bg-slate-700 rounded-full h-2">
                                <div 
                                    className="bg-gradient-to-r from-green-500 to-emerald-400 h-2 rounded-full transition-all duration-500"
                                    style={{ width: `${downloadPercent}%` }}
                                ></div>
                            </div>
                        </div>
                        
                        <div>
                            <div className="flex justify-between items-center mb-1">
                                <span className="text-xs text-slate-400">
                                    <i className="fas fa-arrow-up mr-1"></i>Upload
                                </span>
                                <span className="text-sm font-bold text-blue-400">{formatBytes(upload)}</span>
                            </div>
                            <div className="w-full bg-slate-700 rounded-full h-2">
                                <div 
                                    className="bg-gradient-to-r from-blue-500 to-cyan-400 h-2 rounded-full transition-all duration-500"
                                    style={{ width: `${uploadPercent}%` }}
                                ></div>
                            </div>
                        </div>
                        
                        <div className="pt-3 border-t border-slate-700">
                            <div className="flex justify-between items-center">
                                <span className="text-xs text-slate-400">
                                    <i className="fas fa-chart-bar mr-1"></i>Total
                                </span>
                                <span className="text-lg font-bold text-white">{formatBytes(total)}</span>
                            </div>
                        </div>
                    </div>
                </div>
            );
        };

        // Traffic Distribution Component
        const TrafficDistribution = ({ data }) => {
            const pieData = [
                { name: 'Download', value: data.traffic.total.rx, color: '#10b981' },
                { name: 'Upload', value: data.traffic.total.tx, color: '#3b82f6' }
            ];

            return (
                <div className="glass-effect rounded-2xl p-6 card-hover">
                    <h3 className="text-xl font-bold text-white mb-4">
                        <i className="fas fa-chart-pie text-purple-400 mr-2"></i>
                        Distribuição de Tráfego
                    </h3>
                    <ResponsiveContainer width="100%" height={300}>
                        <PieChart>
                            <Pie
                                data={pieData}
                                cx="50%"
                                cy="50%"
                                labelLine={false}
                                label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(1)}%`}
                                outerRadius={100}
                                fill="#8884d8"
                                dataKey="value"
                            >
                                {pieData.map((entry, index) => (
                                    <Cell key={`cell-${index}`} fill={entry.color} />
                                ))}
                            </Pie>
                            <Tooltip 
                                formatter={(value) => formatBytes(value)}
                                contentStyle={{ 
                                    backgroundColor: 'rgba(15, 23, 42, 0.9)', 
                                    border: '1px solid rgba(148, 163, 184, 0.2)',
                                    borderRadius: '8px',
                                    color: '#fff'
                                }}
                            />
                        </PieChart>
                    </ResponsiveContainer>
                </div>
            );
        };

        // Top 10 Days Component
        const Top10Days = ({ data }) => {
            const topDays = data.traffic.top.slice(0, 10).map(day => ({
                date: formatDate(day.timestamp),
                total: day.rx + day.tx,
                download: day.rx,
                upload: day.tx
            }));

            return (
                <div className="glass-effect rounded-2xl p-6 card-hover">
                    <h3 className="text-xl font-bold text-white mb-4">
                        <i className="fas fa-trophy text-yellow-400 mr-2"></i>
                        Top 10 Dias
                    </h3>
                    <div className="space-y-2 max-h-[300px] overflow-y-auto scrollbar-hide">
                        {topDays.map((day, index) => (
                            <div key={index} className="bg-slate-800/50 rounded-lg p-3 hover:bg-slate-700/50 transition-all">
                                <div className="flex justify-between items-center mb-1">
                                    <span className="text-slate-300 font-medium">
                                        <i className="fas fa-medal text-yellow-400 mr-2"></i>
                                        #{index + 1} {day.date}
                                    </span>
                                    <span className="text-green-400 font-bold">{formatBytes(day.total)}</span>
                                </div>
                                <div className="flex gap-4 text-xs text-slate-400">
                                    <span>
                                        <i className="fas fa-arrow-down mr-1"></i>
                                        {formatBytes(day.download)}
                                    </span>
                                    <span>
                                        <i className="fas fa-arrow-up mr-1"></i>
                                        {formatBytes(day.upload)}
                                    </span>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            );
        };

        // Recent Activity Component
        const RecentActivity = ({ data }) => {
            const recentHours = data.traffic.hour.slice(-12).map(hour => ({
                time: formatTime(hour.timestamp),
                download: hour.rx,
                upload: hour.tx,
                total: hour.rx + hour.tx
            }));

            return (
                <div className="glass-effect rounded-2xl p-6 card-hover">
                    <h3 className="text-xl font-bold text-white mb-4">
                        <i className="fas fa-chart-area text-cyan-400 mr-2"></i>
                        Atividade (Últimas 12 Horas)
                    </h3>
                    <ResponsiveContainer width="100%" height={300}>
                        <AreaChart data={recentHours}>
                            <defs>
                                <linearGradient id="colorDownload" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="5%" stopColor="#10b981" stopOpacity={0.8}/>
                                    <stop offset="95%" stopColor="#10b981" stopOpacity={0}/>
                                </linearGradient>
                                <linearGradient id="colorUpload" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="5%" stopColor="#3b82f6" stopOpacity={0.8}/>
                                    <stop offset="95%" stopColor="#3b82f6" stopOpacity={0}/>
                                </linearGradient>
                            </defs>
                            <CartesianGrid strokeDasharray="3 3" stroke="#334155" />
                            <XAxis dataKey="time" stroke="#94a3b8" />
                            <YAxis stroke="#94a3b8" tickFormatter={(value) => formatBytes(value)} />
                            <Tooltip 
                                formatter={(value) => formatBytes(value)}
                                contentStyle={{ 
                                    backgroundColor: 'rgba(15, 23, 42, 0.9)', 
                                    border: '1px solid rgba(148, 163, 184, 0.2)',
                                    borderRadius: '8px',
                                    color: '#fff'
                                }}
                            />
                            <Legend />
                            <Area type="monotone" dataKey="download" stroke="#10b981" fillOpacity={1} fill="url(#colorDownload)" name="Download" />
                            <Area type="monotone" dataKey="upload" stroke="#3b82f6" fillOpacity={1} fill="url(#colorUpload)" name="Upload" />
                        </AreaChart>
                    </ResponsiveContainer>
                </div>
            );
        };

        // Hours Tab Component
        const HoursTab = ({ data }) => {
            const last24Hours = data.traffic.hour.slice(-24).map(hour => ({
                time: formatTime(hour.timestamp),
                fullDate: new Date(hour.timestamp * 1000).toLocaleString('pt-BR'),
                download: hour.rx,
                upload: hour.tx,
                total: hour.rx + hour.tx
            }));

            return (
                <div className="space-y-6">
                    <div className="glass-effect rounded-2xl p-6 card-hover">
                        <h3 className="text-xl font-bold text-white mb-4">
                            <i className="fas fa-clock text-blue-400 mr-2"></i>
                            Tráfego das Últimas 24 Horas
                        </h3>
                        <ResponsiveContainer width="100%" height={400}>
                            <BarChart data={last24Hours}>
                                <CartesianGrid strokeDasharray="3 3" stroke="#334155" />
                                <XAxis dataKey="time" stroke="#94a3b8" />
                                <YAxis stroke="#94a3b8" tickFormatter={(value) => formatBytes(value)} />
                                <Tooltip 
                                    formatter={(value) => formatBytes(value)}
                                    contentStyle={{ 
                                        backgroundColor: 'rgba(15, 23, 42, 0.9)', 
                                        border: '1px solid rgba(148, 163, 184, 0.2)',
                                        borderRadius: '8px',
                                        color: '#fff'
                                    }}
                                />
                                <Legend />
                                <Bar dataKey="download" fill="#10b981" name="Download" />
                                <Bar dataKey="upload" fill="#3b82f6" name="Upload" />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>

                    <div className="glass-effect rounded-2xl p-6 card-hover">
                        <h3 className="text-xl font-bold text-white mb-4">
                            <i className="fas fa-table text-purple-400 mr-2"></i>
                            Detalhados por Hora:
                        </h3>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left">
                                <thead>
                                    <tr className="border-b border-slate-700">
                                        <th className="pb-3 text-slate-400 font-medium">
                                            <i className="fas fa-clock mr-2"></i>Horário
                                        </th>
                                        <th className="pb-3 text-slate-400 font-medium text-right">
                                            <i className="fas fa-arrow-down mr-2"></i>Download
                                        </th>
                                        <th className="pb-3 text-slate-400 font-medium text-right">
                                            <i className="fas fa-arrow-up mr-2"></i>Upload
                                        </th>
                                        <th className="pb-3 text-slate-400 font-medium text-right">
                                            <i className="fas fa-chart-bar mr-2"></i>Total
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {last24Hours.reverse().map((hour, index) => (
                                        <tr key={index} className="border-b border-slate-800 hover:bg-slate-800/50 transition-all">
                                            <td className="py-3 text-slate-300">{hour.fullDate}</td>
                                            <td className="py-3 text-green-400 text-right font-mono">{formatBytes(hour.download)}</td>
                                            <td className="py-3 text-blue-400 text-right font-mono">{formatBytes(hour.upload)}</td>
                                            <td className="py-3 text-white text-right font-mono font-bold">{formatBytes(hour.total)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            );
        };

        // Days Tab Component
        const DaysTab = ({ data }) => {
            const last30Days = data.traffic.day.slice(-30).map(day => ({
                date: formatDate(day.timestamp),
                download: day.rx,
                upload: day.tx,
                total: day.rx + day.tx
            }));

            return (
                <div className="space-y-6">
                    <div className="glass-effect rounded-2xl p-6 card-hover">
                        <h3 className="text-xl font-bold text-white mb-4">
                            <i className="fas fa-calendar-day text-purple-400 mr-2"></i>
                            Tráfego dos Últimos 30 Dias
                        </h3>
                        <ResponsiveContainer width="100%" height={400}>
                            <LineChart data={last30Days}>
                                <CartesianGrid strokeDasharray="3 3" stroke="#334155" />
                                <XAxis dataKey="date" stroke="#94a3b8" angle={-45} textAnchor="end" height={80} />
                                <YAxis stroke="#94a3b8" tickFormatter={(value) => formatBytes(value)} />
                                <Tooltip 
                                    formatter={(value) => formatBytes(value)}
                                    contentStyle={{ 
                                        backgroundColor: 'rgba(15, 23, 42, 0.9)', 
                                        border: '1px solid rgba(148, 163, 184, 0.2)',
                                        borderRadius: '8px',
                                        color: '#fff'
                                    }}
                                />
                                <Legend />
                                <Line type="monotone" dataKey="download" stroke="#10b981" strokeWidth={2} name="Download" dot={{ r: 3 }} />
                                <Line type="monotone" dataKey="upload" stroke="#3b82f6" strokeWidth={2} name="Upload" dot={{ r: 3 }} />
                            </LineChart>
                        </ResponsiveContainer>
                    </div>

                    <div className="glass-effect rounded-2xl p-6 card-hover">
                        <h3 className="text-xl font-bold text-white mb-4">
                            <i className="fas fa-table text-green-400 mr-2"></i>
                            Dados Detalhados por Dia:
                        </h3>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left">
                                <thead>
                                    <tr className="border-b border-slate-700">
                                        <th className="pb-3 text-slate-400 font-medium">
                                            <i className="fas fa-calendar mr-2"></i>Data
                                        </th>
                                        <th className="pb-3 text-slate-400 font-medium text-right">
                                            <i className="fas fa-arrow-down mr-2"></i>Download
                                        </th>
                                        <th className="pb-3 text-slate-400 font-medium text-right">
                                            <i className="fas fa-arrow-up mr-2"></i>Upload
                                        </th>
                                        <th className="pb-3 text-slate-400 font-medium text-right">
                                            <i className="fas fa-chart-bar mr-2"></i>Total
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {last30Days.reverse().map((day, index) => (
                                        <tr key={index} className="border-b border-slate-800 hover:bg-slate-800/50 transition-all">
                                            <td className="py-3 text-slate-300">{day.date}</td>
                                            <td className="py-3 text-green-400 text-right font-mono">{formatBytes(day.download)}</td>
                                            <td className="py-3 text-blue-400 text-right font-mono">{formatBytes(day.upload)}</td>
                                            <td className="py-3 text-white text-right font-mono font-bold">{formatBytes(day.total)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            );
        };

        // Months Tab Component
        const MonthsTab = ({ data }) => {
            const months = data.traffic.month.map(month => ({
                month: new Date(month.timestamp * 1000).toLocaleDateString('pt-BR', { year: 'numeric', month: 'long' }),
                download: month.rx,
                upload: month.tx,
                total: month.rx + month.tx
            }));

            return (
                <div className="space-y-6">
                    <div className="glass-effect rounded-2xl p-6 card-hover">
                        <h3 className="text-xl font-bold text-white mb-4">
                            <i className="fas fa-calendar-alt text-orange-400 mr-2"></i>
                            Tráfego Mensal
                        </h3>
                        <ResponsiveContainer width="100%" height={400}>
                            <BarChart data={months}>
                                <CartesianGrid strokeDasharray="3 3" stroke="#334155" />
                                <XAxis dataKey="month" stroke="#94a3b8" />
                                <YAxis stroke="#94a3b8" tickFormatter={(value) => formatBytes(value)} />
                                <Tooltip 
                                    formatter={(value) => formatBytes(value)}
                                    contentStyle={{ 
                                        backgroundColor: 'rgba(15, 23, 42, 0.9)', 
                                        border: '1px solid rgba(148, 163, 184, 0.2)',
                                        borderRadius: '8px',
                                        color: '#fff'
                                    }}
                                />
                                <Legend />
                                <Bar dataKey="download" fill="#10b981" name="Download" />
                                <Bar dataKey="upload" fill="#3b82f6" name="Upload" />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {months.reverse().map((month, index) => (
                            <div key={index} className="glass-effect rounded-xl p-5 card-hover">
                                <h4 className="text-slate-300 font-medium mb-3 capitalize">
                                    <i className="fas fa-calendar mr-2"></i>
                                    {month.month}
                                </h4>
                                <div className="space-y-2">
                                    <div className="flex justify-between">
                                        <span className="text-xs text-slate-400">
                                            <i className="fas fa-arrow-down mr-1"></i>Download
                                        </span>
                                        <span className="text-sm text-green-400 font-bold">{formatBytes(month.download)}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-xs text-slate-400">
                                            <i className="fas fa-arrow-up mr-1"></i>Upload
                                        </span>
                                        <span className="text-sm text-blue-400 font-bold">{formatBytes(month.upload)}</span>
                                    </div>
                                    <div className="flex justify-between pt-2 border-t border-slate-700">
                                        <span className="text-xs text-slate-400">
                                            <i className="fas fa-chart-bar mr-1"></i>Total
                                        </span>
                                        <span className="text-base text-white font-bold">{formatBytes(month.total)}</span>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            );
        };


        // Years Tab Component
        const YearsTab = ({ data }) => {
            const years = data.traffic.year ? data.traffic.year.map(year => ({
                year: year.date.year,
                download: year.rx,
                upload: year.tx,
                total: year.rx + year.tx
            })) : [];

            if (years.length === 0) {
                return (
                    <div className="glass-effect rounded-2xl p-8 text-center">
                        <i className="fas fa-calendar text-6xl text-slate-600 mb-4"></i>
                        <p className="text-slate-400">Nenhum dado anual disponível</p>
                    </div>
                );
            }

            const avgPerYear = years.reduce((sum, y) => sum + y.total, 0) / years.length;
            const maxYear = years.reduce((max, y) => y.total > max.total ? y : max, years[0]);

            return (
                <div className="space-y-6">
                    {/* Estatísticas Resumidas */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="glass-effect rounded-xl p-5 card-hover">
                            <div className="flex items-center justify-between mb-2">
                                <h4 className="text-slate-400 text-sm">
                                    <i className="fas fa-calendar-check mr-2"></i>Total de Anos
                                </h4>
                                <i className="fas fa-calendar text-2xl text-blue-400"></i>
                            </div>
                            <p className="text-3xl font-bold text-white">{years.length}</p>
                        </div>

                        <div className="glass-effect rounded-xl p-5 card-hover">
                            <div className="flex items-center justify-between mb-2">
                                <h4 className="text-slate-400 text-sm">
                                    <i className="fas fa-chart-line mr-2"></i>Média Anual
                                </h4>
                                <i className="fas fa-balance-scale text-2xl text-purple-400"></i>
                            </div>
                            <p className="text-3xl font-bold text-white">{formatBytes(avgPerYear)}</p>
                        </div>

                        <div className="glass-effect rounded-xl p-5 card-hover">
                            <div className="flex items-center justify-between mb-2">
                                <h4 className="text-slate-400 text-sm">
                                    <i className="fas fa-crown mr-2"></i>Ano Recorde
                                </h4>
                                <i className="fas fa-trophy text-2xl text-yellow-400"></i>
                            </div>
                            <p className="text-2xl font-bold text-white">{maxYear.year}</p>
                            <p className="text-sm text-slate-400">{formatBytes(maxYear.total)}</p>
                        </div>
                    </div>

                    {/* Gráfico de Barras */}
                    <div className="glass-effect rounded-2xl p-6 card-hover">
                        <h3 className="text-xl font-bold text-white mb-4">
                            <i className="fas fa-chart-bar text-blue-400 mr-2"></i>
                            Tráfego Anual Comparativo
                        </h3>
                        <ResponsiveContainer width="100%" height={400}>
                            <BarChart data={years}>
                                <CartesianGrid strokeDasharray="3 3" stroke="#334155" />
                                <XAxis dataKey="year" stroke="#94a3b8" />
                                <YAxis stroke="#94a3b8" tickFormatter={(value) => formatBytes(value)} />
                                <Tooltip 
                                    formatter={(value) => formatBytes(value)}
                                    contentStyle={{ 
                                        backgroundColor: 'rgba(15, 23, 42, 0.9)', 
                                        border: '1px solid rgba(148, 163, 184, 0.2)',
                                        borderRadius: '8px',
                                        color: '#fff'
                                    }}
                                />
                                <Legend />
                                <Bar dataKey="download" fill="#10b981" name="Download" />
                                <Bar dataKey="upload" fill="#3b82f6" name="Upload" />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>

                    {/* Cards Anuais */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {years.slice().reverse().map((year, index) => (
                            <div key={index} className="glass-effect rounded-xl p-5 card-hover relative overflow-hidden">
                                {year.year === new Date().getFullYear() && (
                                    <div className="absolute top-2 right-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full">
                                        <i className="fas fa-check-circle mr-1"></i>Atual
                                    </div>
                                )}
                                
                                <h4 className="text-2xl font-bold text-white mb-4">
                                    <i className="fas fa-calendar mr-2 text-blue-400"></i>
                                    {year.year}
                                </h4>
                                
                                <div className="space-y-3">
                                    <div className="flex justify-between items-center">
                                        <span className="text-xs text-slate-400">
                                            <i className="fas fa-arrow-down mr-1"></i>Download
                                        </span>
                                        <span className="text-sm text-green-400 font-bold">{formatBytes(year.download)}</span>
                                    </div>
                                    
                                    <div className="flex justify-between items-center">
                                        <span className="text-xs text-slate-400">
                                            <i className="fas fa-arrow-up mr-1"></i>Upload
                                        </span>
                                        <span className="text-sm text-blue-400 font-bold">{formatBytes(year.upload)}</span>
                                    </div>
                                    
                                    <div className="flex justify-between items-center pt-3 border-t border-slate-700">
                                        <span className="text-xs text-slate-400 font-semibold">
                                            <i className="fas fa-chart-bar mr-1"></i>Total
                                        </span>
                                        <span className="text-lg text-white font-bold">{formatBytes(year.total)}</span>
                                    </div>

                                    <div className="pt-2">
                                        <div className="text-xs text-slate-500 mb-1">
                                            vs Média: {((year.total / avgPerYear - 1) * 100).toFixed(1)}%
                                        </div>
                                        <div className="w-full bg-slate-700 rounded-full h-2">
                                            <div 
                                                className={`h-2 rounded-full transition-all duration-500 ${
                                                    year.total > avgPerYear ? 'bg-green-500' : 'bg-blue-500'
                                                }`}
                                                style={{ width: `${Math.min((year.total / maxYear.total) * 100, 100)}%` }}
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            );
        };
        // Footer Component
        const Footer = ({ loadTime, pageLoadTime, interfaceName , systemStats }) => {
            const [currentTime, setCurrentTime] = useState(new Date());
            
            useEffect(() => {
                const timer = setInterval(() => setCurrentTime(new Date()), 1000);
                return () => clearInterval(timer);
            }, []);
            
            return (
                <footer className="fixed bottom-0 left-0 right-0 footer py-3 px-6 z-50">
                    <div className="max-w-7xl mx-auto">
                        <div className="flex flex-col md:flex-row justify-between items-center gap-3 text-xs text-slate-400">
                            <div className="flex items-center gap-4 flex-wrap justify-center md:justify-start">
                                <span className="flex items-center gap-2">
                                    <i className="fas fa-server pulse-icon text-blue-400"></i>
                                    <span className="font-medium text-slate-300">VnStat Dashboard</span>
                                </span>
                                
                                <span className="hidden md:inline text-slate-600">•</span>
                                
                                <span className="flex items-center gap-1">
                                    <i className="fas fa-network-wired text-green-400"></i>
                                    <span className="font-mono">{interfaceName}</span>
                                </span>

                                {systemStats && systemStats.dbSize > 0 && (
                                    <>
                                        <span className="text-slate-600">•</span>
                                        <span className="flex items-center gap-1" title="Tamanho do banco de dados">
                                            <i className="fas fa-database text-purple-400"></i>
                                            <span className="font-medium">{formatBytes(systemStats.dbSize)}</span>
                                        </span>
                                    </>
                                )}
                            </div>
                            
                            <div className="flex items-center gap-4 flex-wrap justify-center">
                                <span className="flex items-center gap-1">
                                    <i className="fas fa-clock text-yellow-400"></i>
                                    {currentTime.toLocaleTimeString('pt-BR')}
                                </span>
                                
                                <span className="text-slate-600">•</span>
                                
                                <span className="flex items-center gap-1">
                                    <i className="fas fa-calendar-alt text-purple-400"></i>
                                    {currentTime.toLocaleDateString('pt-BR')}
                                </span>

                                {systemStats && systemStats.uptime > 0 && (
                                    <>
                                        <span className="text-slate-600">•</span>
                                        <span className="flex items-center gap-1" title="Uptime do sistema">
                                            <i className="fas fa-power-off text-green-400"></i>
                                            {formatUptime(systemStats.uptime)}
                                        </span>
                                    </>
                                )}
                            </div>
                            
                            <div className="flex items-center gap-4 flex-wrap justify-center md:justify-end">
                                {loadTime > 0 && (
                                    <>
                                        <span className="flex items-center gap-1">
                                            <i className="fas fa-bolt text-orange-400"></i>
                                            <span className="font-medium text-slate-300">{loadTime}ms</span>
                                            <span className="text-slate-600 text-xs">JSON</span>
                                        </span>
                                        
                                        <span className="text-slate-600">•</span>
                                    </>
                                )}
                                
                                {pageLoadTime > 0 && (
                                    <>
                                        <span className="flex items-center gap-1">
                                            <i className="fas fa-tachometer-alt text-cyan-400"></i>
                                            <span className="font-medium text-slate-300">{pageLoadTime}ms</span>
                                            <span className="text-slate-600 text-xs">Página</span>
                                        </span>
                                        
                                        <span className="text-slate-600">•</span>
                                    </>
                                )}
                                
                                {systemStats && systemStats.memory && systemStats.memory.total > 0 && (
                                    <>
                                        <span className="flex items-center gap-1" title="Uso de memória RAM">
                                            <i className="fas fa-memory text-pink-400"></i>
                                            <span className="font-medium text-slate-300">
                                                {((systemStats.memory.used / systemStats.memory.total) * 100).toFixed(0)}%
                                            </span>
                                        </span>
                                        
                                        <span className="text-slate-600">•</span>
                                    </>
                                )}
                                
                                <span className="flex items-center gap-1">
                                    <i className="fas fa-code text-pink-400"></i>
                                    <span className="font-medium">React + Recharts</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </footer>
            );
        };

        // Render the app
        const root = ReactDOM.createRoot(document.getElementById('root'));
        root.render(<VnStatDashboard />);
    </script>
</body>

</html>
