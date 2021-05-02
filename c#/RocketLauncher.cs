using System.Collections;
using System.Collections.Generic;
using UnityEngine;

public class RocketLauncher : BaseWeapon
{

    public float UpVector = 50f;
    
    public override void ModProjectle(GameObject bullet, Vector3 target)
    {
        bullet.transform.position += bullet.GetComponent<Rigidbody>().velocity.normalized * 2f + Vector3.up;
        bullet.GetComponent<Rigidbody>().AddForce(-bullet.GetComponent<Rigidbody>().velocity,ForceMode.Impulse);
        bullet.GetComponent<Rigidbody>().AddForce(Vector3.up*UpVector,ForceMode.Impulse);
        if (bullet.GetComponent<Rocket>())
        {
            bullet.GetComponent<Rocket>().target = target;
        }
    }
}
