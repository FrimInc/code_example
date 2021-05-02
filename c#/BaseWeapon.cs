using System;
using System.Collections;
using System.Collections.Generic;
using UnityEngine;

public class BaseWeapon : MonoBehaviour
{

    public int code = 0;

    public string WepName = "Пушка";
    
    public GameObject bullet, target, bulletsParent;
    
    public float shootPeriod = 0.5f;
    public float cost = 0.05f;
    
    public AudioSource audioData;
    public AudioClip sound;
    
    protected float nextShoot = 0f;

    void Start()
    {
        audioData = GetComponent<AudioSource>();
    }

    // Update is called once per frame
    void FixedUpdate()
    {
        if (Input.GetMouseButton(0))
        {
            if (GetComponent<WASD>().ammo < cost)
            {
                return;
            }
            if (nextShoot < Time.timeSinceLevelLoad)
            {
                GetComponent<WASD>().ammo -= cost;
                nextShoot = Time.timeSinceLevelLoad + shootPeriod;
                GameObject gb = Instantiate(bullet, transform.position+(target.transform.position - transform.position).normalized*1f, Quaternion.identity);
                gb.GetComponent<Rigidbody>().AddForce((target.transform.position - transform.position).normalized * 50f,ForceMode.Impulse);
                gb.transform.SetParent(bulletsParent.transform);
                ModProjectle(gb,target.transform.position);
                if (gb.GetComponent<Bullet>())
                {
                    gb.GetComponent<Bullet>().doStart();    
                } else if (gb.GetComponent<Rocket>())
                {
                    gb.GetComponent<Rocket>().doStart();    
                }

                if (sound)
                {
                    audioData.clip = sound;
                    audioData.Play(0);    
                }
                

            }
          
        }
    }

    public virtual void ModProjectle(GameObject bullet, Vector3 target)
    {
        
    }

    
}
