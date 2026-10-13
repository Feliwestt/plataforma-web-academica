import React, { useEffect, useRef, useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';

export default function Dashboard({ auth, cobertura = [] }) {
    // Vista activa persistente en la URL (?vista=importar): sobrevive al
    // redirect del POST y al F5, y queda marcable.
    const [vistaActiva, setVistaActiva] = useState(() => (
        new URLSearchParams(window.location.search).get('vista') || 'estadisticas'
    ));

    const cambiarVista = (vista) => {
        setVistaActiva(vista);
        window.history.replaceState(null, '', `${window.location.pathname}?vista=${vista}`);
    };

    // Seguimiento en vivo de la cola de importación (polling cada 2s).
    // Vive en el servidor: al abrir la vista se recarga aunque se haya
    // navegado o recargado en mitad del proceso.
    const [seguimiento, setSeguimiento] = useState([]);
    const [rastreando, setRastreando] = useState(false);
    // Historial visible: últimas 10, "mostrar más" suma de a 10.
    const [visibles, setVisibles] = useState(10);
    const limiteRef = useRef(20);

    const cargarEstado = async () => {
        try {
            const respuesta = await fetch(route('admin.importaciones.estado', { limite: limiteRef.current }), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store',
            });
            const filas = await respuesta.json();
            setSeguimiento(filas);
            if (filas.some((f) => f.estado === 'EN_COLA' || f.estado === 'PROCESANDO')) {
                setRastreando(true);
            }
            return filas;
        } catch (e) {
            return seguimiento;
        }
    };

    const mostrarMas = () => {
        limiteRef.current += 10;
        setVisibles((v) => v + 10);
        cargarEstado();
    };

    // Borrado por curso (solo desarrollo): confirma y elimina entero.
    const eliminarCurso = (c) => {
        if (!window.confirm(`Borrar ${c.nivel}°${c.letra} ${c.anio} entero (${c.alumnos} alumnos, ${c.notas} notas)? Esta acción no se puede deshacer.`)) {
            return;
        }
        router.delete(route('admin.cursos.destroy'), {
            data: { nivel: c.nivel, letra: c.letra, anio: c.anio },
            preserveScroll: true,
            onSuccess: () => router.reload({ only: ['cobertura'] }),
        });
    };

    const formatearFecha = (iso) => {
        if (!iso) return '';
        const d = new Date(String(iso).replace(' ', 'T'));
        return d.toLocaleString('es-CL', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    };

    // Historial agrupado por lote de subida (los sin lote van solos),
    // recortado a las filas visibles.
    const gruposHistorial = (() => {
        const mapa = new Map();
        const orden = [];
        seguimiento.slice(0, visibles).forEach((f) => {
            const clave = f.lote || `solo-${f.id}`;
            if (!mapa.has(clave)) {
                mapa.set(clave, []);
                orden.push(clave);
            }
            mapa.get(clave).push(f);
        });
        return orden.map((clave) => ({ clave, filas: mapa.get(clave) }));
    })();

    useEffect(() => {
        if (vistaActiva === 'importar') {
            cargarEstado();
        }
    }, [vistaActiva]);

    useEffect(() => {
        if (!rastreando) return;
        const intervalo = setInterval(async () => {
            const filas = await cargarEstado();
            const pendientes = filas.some((f) => f.estado === 'EN_COLA' || f.estado === 'PROCESANDO');
            if (!pendientes) {
                setRastreando(false);
                router.reload({ only: ['cobertura'] });
            }
        }, 2000);
        return () => clearInterval(intervalo);
    }, [rastreando]);

    // Variables globales para los mensajes de éxito o error
    const { flash, resumenImportacion = [] } = usePage().props;

    // Configuración del formulario de importación masiva
    const { data, setData, post, processing, errors, progress } = useForm({
        archivos_excel: [],
    });

    // Función para enviar los Excels (uno o varios a la vez)
    const submitImportacion = (e) => {
        e.preventDefault();
        if (!data.archivos_excel.length) return;
        post(route('admin.importar.store'), {
            forceFormData: true,
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                // Opcional: limpiar el formulario tras éxito
                setData('archivos_excel', []);
                document.getElementById('excel-upload').value = '';
                // La cola responde al instante: traer el estado ya y rastrear
                cargarEstado();
                setRastreando(true);
            }
        });
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Panel de Dirección" />

            <div className="max-w-screen-2xl mx-auto flex flex-col md:flex-row min-h-[calc(100vh-4rem)]">
                
                {/* BARRA LATERAL IZQUIERDA (Sidebar del Director) */}
                <aside className="w-full md:w-72 bg-white border-r border-gray-200 shadow-sm flex-shrink-0 relative z-10">
                    <div className="p-6">
                        <h2 className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">
                            Centro de Mando
                        </h2>
                        <nav className="space-y-2 mb-6">
                            <button
                                onClick={() => cambiarVista('estadisticas')}
                                className={`w-full text-left px-4 py-3 rounded-lg transition-all duration-200 flex items-center gap-3 group ${
                                    vistaActiva === 'estadisticas' ? 'bg-[#002855] text-white shadow-md' : 'text-gray-600 hover:bg-blue-50 hover:text-[#002855]'
                                }`}
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                <span className="font-semibold text-sm">Estadísticas Globales</span>
                            </button>

                            <button
                                onClick={() => cambiarVista('profesores')}
                                className={`w-full text-left px-4 py-3 rounded-lg transition-all duration-200 flex items-center gap-3 group ${
                                    vistaActiva === 'profesores' ? 'bg-[#B91C1C] text-white shadow-md' : 'text-gray-600 hover:bg-red-50 hover:text-[#B91C1C]'
                                }`}
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                <span className="font-semibold text-sm">Plantel Docente</span>
                            </button>
                        </nav>

                        {/* GESTIÓN DE DATOS */}
                        <h2 className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Gestión de Datos</h2>
                        <nav className="space-y-2 mb-6">
                            <button
                                onClick={() => cambiarVista('importar')}
                                className={`w-full text-left px-4 py-3 rounded-lg transition-all duration-200 flex items-center gap-3 group ${
                                    vistaActiva === 'importar' ? 'bg-purple-600 text-white shadow-md' : 'text-gray-600 hover:bg-purple-50 hover:text-purple-700'
                                }`}
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                <span className="font-semibold text-sm">Importar Notas</span>
                            </button>
                        </nav>

                        <h2 className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Herramientas Avanzadas</h2>
                        <nav className="space-y-2">
                            <button
                                onClick={() => cambiarVista('ia')}
                                className={`w-full text-left px-4 py-3 rounded-lg transition-all duration-200 flex items-center gap-3 group ${
                                    vistaActiva === 'ia' ? 'bg-[#002855] text-white shadow-md' : 'text-gray-600 hover:bg-blue-50 hover:text-[#002855]'
                                }`}
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                <span className="font-semibold text-sm">Gestión predictiva IA</span>
                            </button>
                        </nav>
                    </div>
                </aside>

                {/* ÁREA PRINCIPAL DERECHA */}
                <main className="flex-1 p-6 md:p-8 bg-gray-50 overflow-y-auto">
                    <AnimatePresence mode="wait">
                        
                        {vistaActiva === 'estadisticas' && (
                            <motion.div key="stats" initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -20 }} className="space-y-6">
                                <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-200 border-l-4 border-l-[#002855]">
                                    <h1 className="text-2xl font-bold text-[#002855]">Visión General del Colegio</h1>
                                    <p className="text-gray-500 text-sm mt-1">Monitoreo de rendimiento académico institucional.</p>
                                </div>
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div className="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4">
                                        <div className="p-3 bg-blue-50 text-[#002855] rounded-lg"><svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg></div>
                                        <div><p className="text-sm text-gray-500 font-semibold">Total Matrícula</p><p className="text-2xl font-bold text-gray-800">Cargando...</p></div>
                                    </div>
                                    <div className="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4">
                                        <div className="p-3 bg-red-50 text-[#B91C1C] rounded-lg"><svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg></div>
                                        <div><p className="text-sm text-gray-500 font-semibold">Promedio Institucional</p><p className="text-2xl font-bold text-gray-800">-</p></div>
                                    </div>
                                </div>
                            </motion.div>
                        )}

                        {/* VISTA 2: IMPORTAR NOTAS */}
                        {vistaActiva === 'importar' && (
                            <motion.div key="import" initial={{ opacity: 0, x: 20 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: -20 }} className="space-y-6">
                                <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-200 border-l-4 border-l-purple-600">
                                    <h1 className="text-2xl font-bold text-purple-800">Módulo ETL: Importar Nómina y Calificaciones</h1>
                                    <p className="text-gray-500 text-sm mt-1">Sube uno o varios archivos Excel para actualizar la base de datos de manera segura. Cada archivo se procesa por separado.</p>
                                </div>

                                {/* MATRIZ DE COBERTURA: qué cursos ya están subidos */}
                                <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                                    <h2 className="text-lg font-bold text-gray-800">Cursos subidos (1° a 4° medio, A–C)</h2>
                                    <p className="text-gray-500 text-sm mt-1 mb-2">Verde = subido, amarillo = parcial (sin notas), gris = falta subir.</p>
                                    <div className="mb-4 p-3 bg-amber-50 border border-amber-300 text-amber-800 rounded-lg text-sm font-semibold">
                                        Zona de desarrollo: la papelera de cada curso borra sus alumnos y notas. Solo disponible en desarrollo.
                                    </div>
                                    <div className="grid grid-cols-3 gap-3">
                                        {cobertura.map((c) => (
                                            <div
                                                key={`${c.nivel}${c.letra}`}
                                                className={`relative p-3 rounded-lg border text-center ${
                                                    c.estado === 'subido'
                                                        ? 'bg-green-50 border-green-300'
                                                        : c.estado === 'parcial'
                                                            ? 'bg-yellow-50 border-yellow-300'
                                                            : 'bg-gray-50 border-gray-200'
                                                }`}
                                            >
                                                {c.estado !== 'no_subido' && (
                                                    <button
                                                        type="button"
                                                        title={`Borrar ${c.nivel}°${c.letra} ${c.anio}`}
                                                        onClick={() => eliminarCurso(c)}
                                                        className="absolute top-1 right-1 w-6 h-6 flex items-center justify-center rounded-full text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                                    >
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                )}
                                                <p className="font-bold text-gray-800">{c.nivel}°{c.letra}</p>
                                                <p className={`text-xs font-semibold ${
                                                    c.estado === 'subido' ? 'text-green-700' : c.estado === 'parcial' ? 'text-yellow-700' : 'text-gray-400'
                                                }`}>
                                                    {c.estado === 'subido' ? 'Subido' : c.estado === 'parcial' ? 'Parcial' : 'Falta subir'}
                                                </p>
                                                {(c.estado !== 'no_subido') && (
                                                    <p className="text-xs text-gray-500 mt-1">{c.alumnos} alumnos · {c.notas} notas</p>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                <div className="bg-white p-8 rounded-xl shadow-sm border border-gray-200">
                                    
                                    {flash?.success && (
                                        <div className="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg flex items-center gap-3 font-semibold text-sm">
                                            <svg className="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path></svg>
                                            {flash.success}
                                        </div>
                                    )}
                                    
                                    {flash?.error && (
                                        <div className="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg flex items-center gap-3 font-semibold text-sm">
                                            <svg className="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            {flash.error}
                                        </div>
                                    )}

                                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                        <div>
                                    {/* Resultado por archivo de la última importación masiva */}
                                    {resumenImportacion.length > 0 && (
                                        <div className="mb-6 space-y-2">
                                            {resumenImportacion.map((r) => (
                                                <div
                                                    key={r.archivo}
                                                    className={`p-3 rounded-lg border text-sm font-semibold flex items-center gap-3 ${
                                                        r.estado === 'ok'
                                                            ? 'bg-green-50 border-green-200 text-green-800'
                                                            : r.estado === 'repetido'
                                                                ? 'bg-yellow-50 border-yellow-200 text-yellow-800'
                                                                : 'bg-red-50 border-red-200 text-red-800'
                                                    }`}
                                                >
                                                    <span className="font-bold">{r.archivo}</span>
                                                    <span>{r.mensaje}</span>
                                                </div>
                                            ))}
                                        </div>
                                    )}

                                    <div className="mb-4 p-3 bg-blue-50 border border-blue-200 text-blue-800 rounded-lg text-sm">
                                        <span className="font-bold">Solo 1 archivo por vez:</span> cada Excel trae miles de datos. Subir varios a la vez satura el servidor
                                    </div>
                                    <form onSubmit={submitImportacion} className="space-y-6">
                                        <div>
                                            <label className="block text-sm font-bold text-gray-700 mb-2">
                                                Seleccionar Archivo Excel (.xlsx / .xls / .csv)
                                            </label>
                                            <div className="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:bg-gray-50 transition-colors">
                                                <input
                                                    id="excel-upload"
                                                    type="file"
                                                    accept=".xlsx, .xls, .csv"
                                                    onChange={e => setData('archivos_excel', Array.from(e.target.files).slice(0, 1))}
                                                    className="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-6 file:rounded-full file:border-0 file:text-sm file:font-bold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 cursor-pointer"
                                                />
                                            </div>
                                            {data.archivos_excel.length > 0 && (
                                                <ul className="mt-3 space-y-1 text-sm text-gray-600">
                                                    {data.archivos_excel.map((f) => (
                                                        <li key={f.name}>• {f.name}</li>
                                                    ))}
                                                </ul>
                                            )}
                                            {(errors.archivos_excel || errors['archivos_excel.0']) && (
                                                <div className="text-red-500 text-sm mt-2 font-semibold">
                                                    {errors.archivos_excel ?? errors['archivos_excel.0']}
                                                </div>
                                            )}
                                        </div>

                                        {/* Barra de proceso de la subida */}
                                        {progress && (
                                            <div className="w-full bg-gray-200 rounded-full h-3">
                                                <div
                                                    className="bg-purple-600 h-3 rounded-full transition-all"
                                                    style={{ width: `${progress.percentage}%` }}
                                                />
                                            </div>
                                        )}

                                        <button
                                            type="submit"
                                            disabled={processing || !data.archivos_excel.length}
                                            className="w-full bg-purple-600 text-white p-3 rounded-lg font-bold hover:bg-purple-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                                        >
                                            {processing ? (
                                                <>
                                                    <svg className="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                    Procesando...
                                                </>
                                            ) : 'Iniciar Importación Segura'}
                                        </button>
                                    </form>
                                        </div>
                                        <div>
                                            <h2 className="text-lg font-bold text-gray-800">Historial de subidas</h2>
                                            <p className="text-gray-500 text-sm mt-1 mb-4">Se muestran las últimas 10 importaciones con su estado en vivo. Usa Mostrar más para ver anteriores.</p>
                                            {/* Repetidos o con error: se frenan al recibirlos, sin fila en DB: van fijos arriba */}
                                            {resumenImportacion.filter((r) => r.estado !== 'en_cola').map((r) => (
                                                <div
                                                    key={`aviso-${r.archivo}`}
                                                    className={`mb-2 p-3 rounded-lg border text-sm font-semibold flex items-center gap-3 ${
                                                        r.estado === 'repetido'
                                                            ? 'bg-yellow-50 border-yellow-200 text-yellow-800'
                                                            : 'bg-red-50 border-red-200 text-red-800'
                                                    }`}
                                                >
                                                    <span className="font-bold">{r.archivo}</span>
                                                    <span>{r.mensaje}</span>
                                                </div>
                                            ))}
                                            {gruposHistorial.length > 0 ? (
                                                <div className="space-y-4">
                                                    {gruposHistorial.map((g) => (
                                                        <div key={g.clave}>
                                                            <p className="text-xs font-bold text-gray-500 mb-2">
                                                                {g.filas.length > 1
                                                                    ? `Lote de ${g.filas.length} archivos · ${formatearFecha(g.filas[0].iniciada_en)}`
                                                                    : formatearFecha(g.filas[0].iniciada_en)}
                                                            </p>
                                                            <div className="space-y-2">
                                                                {g.filas.map((f) => {
                                                        const pct = f.total > 0 ? Math.min(100, Math.round((f.insertadas / f.total) * 100)) : 0;
                                                        return (
                                                            <div
                                                                key={f.id}
                                                                className={`p-3 rounded-lg border text-sm ${
                                                                    f.estado === 'PROCESADA'
                                                                        ? 'bg-green-50 border-green-200 text-green-800'
                                                                        : f.estado === 'ERROR'
                                                                            ? 'bg-red-50 border-red-200 text-red-800'
                                                                            : f.estado === 'REPETIDO'
                                                                                ? 'bg-yellow-50 border-yellow-200 text-yellow-800'
                                                                                : 'bg-blue-50 border-blue-200 text-blue-800'
                                                                }`}
                                                            >
                                                                <div className="flex items-center gap-3 font-semibold">
                                                                    <span className="font-bold">{f.archivo}</span>
                                                                    <span>
                                                                        {f.estado === 'PROCESADA'
                                                                            ? `${f.insertadas} nuevas, ${f.omitidas} omitidas`
                                                                            : f.estado === 'ERROR'
                                                                                ? `Error: ${f.error}`
                                                                                : f.estado === 'REPETIDO'
                                                                                    ? 'Repetido · 0 cambios en total'
                                                                                    : f.estado === 'PROCESANDO'
                                                                                        ? `Procesando… ${f.insertadas}${f.total ? `/${f.total}` : ''}`
                                                                                        : 'En cola…'}
                                                                    </span>
                                                                </div>
                                                                {(f.estado === 'EN_COLA' || f.estado === 'PROCESANDO') && (
                                                                    <div className="mt-2 w-full bg-blue-100 rounded-full h-2">
                                                                        <div
                                                                            className="bg-blue-600 h-2 rounded-full transition-all"
                                                                            style={{ width: `${f.total > 0 ? pct : 50}%` }}
                                                                        />
                                                                    </div>
                                                                )}
                                                            </div>
                                                        );
                                                    })}
                                                </div>
                                                        </div>
                                                    ))}
                                                    {(seguimiento.length > visibles || seguimiento.length >= limiteRef.current) && (
                                                        <button
                                                            type="button"
                                                            onClick={mostrarMas}
                                                            className="mt-3 w-full py-2 text-sm font-bold text-purple-700 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors"
                                                        >
                                                            Mostrar más
                                                        </button>
                                                    )}
                                                </div>
                                            ) : (
                                                <p className="text-sm text-gray-400">Sin movimientos aún. Al subir archivos aparecerán aquí con su estado.</p>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </motion.div>
                        )}

                        {/* Vistas en construcción */}
                        {(vistaActiva === 'profesores' || vistaActiva === 'ia') && (
                            <motion.div key="construccion" initial={{ opacity: 0, scale: 0.95 }} animate={{ opacity: 1, scale: 1 }} exit={{ opacity: 0, scale: 0.95 }} className="flex flex-col items-center justify-center min-h-[60vh]">
                                <div className="bg-white p-10 rounded-2xl shadow-sm border border-gray-200 text-center max-w-md w-full">
                                    <h2 className="text-2xl font-bold text-[#002855] mb-2">{vistaActiva === 'profesores' ? 'Gestión de Profesores' : 'Predicción Académica IA'}</h2>
                                    <p className="text-gray-500 mb-8">Esta sección administrativa está actualmente en fase de desarrollo.</p>
                                    <div className="inline-block px-4 py-2 bg-yellow-100 text-yellow-800 rounded-full text-sm font-bold border border-yellow-200">🚧 EN CONSTRUCCIÓN 🚧</div>
                                </div>
                            </motion.div>
                        )}

                    </AnimatePresence>
                </main>
            </div>
        </AuthenticatedLayout>
    );
}