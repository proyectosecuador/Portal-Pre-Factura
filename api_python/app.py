#!/usr/bin/env python
# -*- coding: utf-8 -*-

"""
API Python para procesar archivos de recepción y despacho
"""

import os
import sys
import json
import logging
from datetime import datetime
from flask import Flask, request, jsonify
from flask_cors import CORS

# ============================================
# CONFIGURAR RUTAS - IMPORTANTE
# ============================================
# Obtener la ruta base del proyecto
BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
sys.path.insert(0, BASE_DIR)  # Agregar la raíz del proyecto al path

print(f"📁 BASE_DIR: {BASE_DIR}")

# Configurar logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

# ============================================
# IMPORTAR PROCESADOR DE RECEPCIÓN
# ============================================
RecepcionProcesador = None

try:
    from procesadores.recepcion_procesador import RecepcionProcesador
    logger.info("✅ Procesador de recepción cargado correctamente")
except ImportError as e:
    logger.error(f"❌ Error importando procesador de recepción: {e}")
    
    archivo_procesador = os.path.join(BASE_DIR, 'procesadores', 'recepcion_procesador.py')
    if os.path.exists(archivo_procesador):
        logger.info(f"   ✅ El archivo existe en: {archivo_procesador}")
        import importlib.util
        spec = importlib.util.spec_from_file_location("recepcion_procesador", archivo_procesador)
        recepcion_procesador = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(recepcion_procesador)
        RecepcionProcesador = recepcion_procesador.RecepcionProcesador
        logger.info("   ✅ Procesador de recepción cargado manualmente")
    else:
        logger.error(f"   ❌ El archivo NO existe")
        class RecepcionProcesador:
            def procesar(self, archivo_path, fecha_desde=None, fecha_hasta=None, request_id=None):
                return {
                    'success': True,
                    'total_registros': 0,
                    'headers': [],
                    'data': [],
                    'stats': {'error': 'Procesador no disponible'},
                    'metadata': {'error': 'Procesador no disponible'}
                }

# ============================================
# IMPORTAR PROCESADOR DE DESPACHO
# ============================================
DespachoProcesador = None

try:
    from procesadores.despacho_procesador import DespachoProcesador
    logger.info("✅ Procesador de despacho cargado correctamente")
except ImportError as e:
    logger.error(f"❌ Error importando procesador de despacho: {e}")
    
    archivo_procesador = os.path.join(BASE_DIR, 'procesadores', 'despacho_procesador.py')
    if os.path.exists(archivo_procesador):
        logger.info(f"   ✅ El archivo existe en: {archivo_procesador}")
        import importlib.util
        spec = importlib.util.spec_from_file_location("despacho_procesador", archivo_procesador)
        despacho_procesador = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(despacho_procesador)
        DespachoProcesador = despacho_procesador.DespachoProcesador
        logger.info("   ✅ Procesador de despacho cargado manualmente")
    else:
        logger.error(f"   ❌ El archivo NO existe")
        class DespachoProcesador:
            def procesar(self, archivo_path, fecha_desde=None, fecha_hasta=None, request_id=None):
                return {
                    'success': True,
                    'total_registros': 0,
                    'headers': [],
                    'data': [],
                    'stats': {'error': 'Procesador no disponible'},
                    'metadata': {'error': 'Procesador no disponible'}
                }

app = Flask(__name__)
CORS(app)

# Directorio para archivos temporales
TEMP_DIR = os.path.join(os.path.dirname(__file__), 'temp')
os.makedirs(TEMP_DIR, exist_ok=True)

@app.route('/health', methods=['GET'])
def health_check():
    """Endpoint para verificar que la API está funcionando"""
    return jsonify({
        'status': 'ok',
        'timestamp': datetime.now().isoformat(),
        'version': '1.0.0',
        'base_dir': BASE_DIR,
        'recepcion_available': RecepcionProcesador is not None,
        'despacho_available': DespachoProcesador is not None
    })

@app.route('/process_recepcion', methods=['POST'])
def process_recepcion():
    """Endpoint para procesar archivos de recepción"""
    try:
        if 'archivo' not in request.files:
            return jsonify({'success': False, 'error': 'No se recibió archivo'}), 400
        
        file = request.files['archivo']
        if file.filename == '':
            return jsonify({'success': False, 'error': 'Archivo vacío'}), 400
        
        extension = file.filename.split('.')[-1].lower()
        if extension not in ['xls', 'xlsx', 'csv']:
            return jsonify({'success': False, 'error': f'Formato no válido: {extension}'}), 400
        
        temp_file = os.path.join(TEMP_DIR, f'recepcion_{datetime.now().strftime("%Y%m%d_%H%M%S")}_{file.filename}')
        file.save(temp_file)
        logger.info(f"📁 Archivo recepción guardado: {temp_file}")
        
        fecha_desde = request.form.get('fecha_desde')
        fecha_hasta = request.form.get('fecha_hasta')
        request_id = datetime.now().strftime("%Y%m%d_%H%M%S")
        
        logger.info(f"📊 Procesando recepción: {file.filename}")
        logger.info(f"   Fechas: desde={fecha_desde}, hasta={fecha_hasta}")
        
        procesador = RecepcionProcesador()
        resultado = procesador.procesar(temp_file, fecha_desde, fecha_hasta, request_id)
        
        try:
            os.unlink(temp_file)
        except:
            pass
        
        logger.info(f"✅ Recepción procesada: {resultado.get('total_registros', 0)} registros")
        
        return jsonify(resultado)
        
    except Exception as e:
        logger.error(f"❌ Error en recepción: {str(e)}", exc_info=True)
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/process_despacho', methods=['POST'])
def process_despacho():
    """Endpoint para procesar archivos de despacho"""
    try:
        if 'archivo' not in request.files:
            return jsonify({'success': False, 'error': 'No se recibió archivo'}), 400
        
        file = request.files['archivo']
        if file.filename == '':
            return jsonify({'success': False, 'error': 'Archivo vacío'}), 400
        
        extension = file.filename.split('.')[-1].lower()
        if extension not in ['xls', 'xlsx', 'csv']:
            return jsonify({'success': False, 'error': f'Formato no válido: {extension}'}), 400
        
        temp_file = os.path.join(TEMP_DIR, f'despacho_{datetime.now().strftime("%Y%m%d_%H%M%S")}_{file.filename}')
        file.save(temp_file)
        logger.info(f"📁 Archivo despacho guardado: {temp_file}")
        
        fecha_desde = request.form.get('fecha_desde')
        fecha_hasta = request.form.get('fecha_hasta')
        request_id = datetime.now().strftime("%Y%m%d_%H%M%S")
        
        # DEBUG: Imprimir los parámetros recibidos
        print(f"\n{'='*60}")
        print(f"🔍 DEBUG - Procesando DESPACHO")
        print(f"🔍 Archivo: {file.filename}")
        print(f"🔍 fecha_desde recibida: '{fecha_desde}'")
        print(f"🔍 fecha_hasta recibida: '{fecha_hasta}'")
        print(f"🔍 Todas las claves en form: {list(request.form.keys())}")
        print(f"{'='*60}\n")
        
        logger.info(f"📊 Procesando despacho: {file.filename}")
        logger.info(f"   Fechas: desde={fecha_desde}, hasta={fecha_hasta}")
        
        procesador = DespachoProcesador()
        resultado = procesador.procesar(temp_file, fecha_desde, fecha_hasta, request_id)
        
        try:
            os.unlink(temp_file)
            logger.info(f"🗑️ Archivo eliminado: {temp_file}")
        except:
            pass
        
        logger.info(f"✅ Despacho procesado: {resultado.get('total_registros', 0)} registros")
        
        return jsonify(resultado)
        
    except Exception as e:
        logger.error(f"❌ Error en despacho: {str(e)}", exc_info=True)
        return jsonify({'success': False, 'error': str(e)}), 500

if __name__ == '__main__':
    port = int(os.environ.get('PORT', 5000))
    print(f"\n🚀 API Python iniciada en http://localhost:{port}")
    print(f"   Endpoints:")
    print(f"   - GET  /health")
    print(f"   - POST /process_recepcion")
    print(f"   - POST /process_despacho")
    print(f"   Base dir: {BASE_DIR}")
    print(f"   Recepción disponible: {RecepcionProcesador is not None}")
    print(f"   Despacho disponible: {DespachoProcesador is not None}\n")
    app.run(host='0.0.0.0', port=port, debug=True)